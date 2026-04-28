<?php

use App\Enums\DocumentType;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Enums\SealStatus;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\DocumentsRelationManager;
use App\Jobs\RecalculateProductCompleteness;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Product;
use App\Models\ProductSafetyEntry;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->distributor = Distributor::factory()->create(['slug' => 'acme-corp']);
    $this->owner = User::factory()->create();

    $this->distributor->members()->attach($this->owner, ['role' => Role::Owner->value]);

    $this->supplier = Supplier::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $this->brand = Brand::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
    ]);

    $this->category = Category::factory()->create();
});

test('product completeness reflects required documents and safety fields', function () {
    $template = Template::factory()->create([
        'category_id' => $this->category->id,
        'required_document_types' => [DocumentType::Manual->value, DocumentType::DeclarationOfConformity->value],
        'required_data_fields' => ['safety_text', 'warning_text'],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $this->category->id,
        'template_id' => $template->id,
    ]);

    Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
    ]);

    ProductSafetyEntry::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'safety_text' => 'Keep away from open flames.',
        'warning_text' => null,
    ]);

    $product->refresh();

    expect($product->completedComplianceItemCount())->toBe(2)
        ->and($product->requiredComplianceItemCount())->toBe(4)
        ->and($product->calculateCompletenessScore())->toBe(50.0)
        ->and($product->completeness_score)->toBe('50.00')
        ->and($product->missingRequiredDocumentTypes())->toBe(['Declaration of conformity'])
        ->and($product->missingRequiredSafetyFields())->toBe(['Warning text'])
        ->and($product->completenessSummary())->toBe('2 of 4 required items are present.')
        ->and($product->missingRequirementsSummary())->toBe('Missing required documents: Declaration of conformity. Missing required safety fields: Warning text.');
});

test('product completeness score stays in sync when documents and safety entries change', function () {
    $template = Template::factory()->create([
        'category_id' => $this->category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => ['safety_text'],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $this->category->id,
        'template_id' => $template->id,
    ]);

    expect($product->fresh()->completeness_score)->toBe('0.00');

    $document = Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
    ]);

    expect($product->fresh()->completeness_score)->toBe('50.00');

    $entry = ProductSafetyEntry::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'safety_text' => 'Keep away from heat sources.',
    ]);

    expect($product->fresh()->completeness_score)->toBe('100.00');

    $entry->update([
        'safety_text' => null,
    ]);

    expect($product->fresh()->completeness_score)->toBe('50.00');

    $document->delete();

    expect($product->fresh()->completeness_score)->toBe('0.00');
});

test('updating template requirements refreshes related product completeness', function () {
    $template = Template::factory()->create([
        'category_id' => $this->category->id,
        'required_document_types' => [],
        'required_data_fields' => [],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $this->category->id,
        'template_id' => $template->id,
    ]);

    Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
    ]);

    expect($product->fresh()->completeness_score)->toBe('0.00');

    $template->update([
        'required_document_types' => [DocumentType::Manual->value],
    ]);

    expect($product->fresh()->completeness_score)->toBe('100.00');
});

test('template updates only queue completeness recalculation when requirements change', function () {
    Queue::fake();

    $template = Template::factory()->create([
        'category_id' => $this->category->id,
        'required_document_types' => [],
        'required_data_fields' => [],
    ]);

    $template->update([
        'name' => 'Renamed Template',
    ]);

    Queue::assertNothingPushed();

    $template->update([
        'required_document_types' => [DocumentType::Manual->value],
    ]);

    Queue::assertPushed(RecalculateProductCompleteness::class);
});

test('products can only be submitted for review when fully complete', function () {
    $template = Template::factory()->create([
        'category_id' => $this->category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => [],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $this->category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::Open,
    ]);

    expect($product->canBeSubmittedForReview())->toBeFalse()
        ->and($product->submitForReview())->toBeFalse()
        ->and($product->fresh()->status)->toBe(ProductStatus::Open);

    Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
    ]);

    expect($product->fresh()->canBeSubmittedForReview())->toBeTrue()
        ->and($product->fresh()->submitForReview())->toBeTrue()
        ->and($product->fresh()->status)->toBe(ProductStatus::UnderReview)
        ->and($product->fresh()->canBeSubmittedForReview())->toBeFalse();
});

test('seal status is computed from approval and completeness without an override field', function () {
    $template = Template::factory()->create([
        'category_id' => $this->category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => [],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $this->category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::Open,
    ]);

    expect($product->sealStatus())->toBe(SealStatus::NotVerified);

    Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
    ]);

    expect($product->fresh()->sealStatus())->toBe(SealStatus::InProgress);

    $product->update([
        'status' => ProductStatus::Approved,
    ]);

    expect($product->fresh()->sealStatus())->toBe(SealStatus::Verified);
});

test('documents relation manager flags missing required document types', function () {
    $template = Template::factory()->create([
        'category_id' => $this->category->id,
        'required_document_types' => [DocumentType::Manual->value, DocumentType::DeclarationOfConformity->value],
        'required_data_fields' => [],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $this->category->id,
        'template_id' => $template->id,
    ]);

    Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
    ]);

    expect(DocumentsRelationManager::getTitle($product, EditProduct::class))->toBe('Documents')
        ->and(DocumentsRelationManager::getBadge($product, EditProduct::class))->toBeNull()
        ->and(DocumentsRelationManager::getBadgeColor($product, EditProduct::class))->toBe('danger')
        ->and(DocumentsRelationManager::getBadgeTooltip($product, EditProduct::class))->toBeNull()
        ->and(DocumentsRelationManager::getMissingRequiredDocumentTypesMessage($product))->toBe('Missing required document types: Declaration of conformity.');
});

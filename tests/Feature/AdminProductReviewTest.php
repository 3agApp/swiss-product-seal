<?php

use App\Enums\DocumentType;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Filament\Resources\Products\AdminProductResource;
use App\Filament\Resources\Products\Pages\EditAdminProduct;
use App\Filament\Resources\Products\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Products\RelationManagers\SafetyEntriesRelationManager;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductSafetyEntry;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->systemAdmin = User::factory()->create(['email' => 'system-admin@example.com']);
    config()->set('admin.allowed_emails', [$this->systemAdmin->email]);

    $this->organization = Organization::factory()->create();
    $this->organization->members()->attach($this->systemAdmin, ['role' => Role::Owner->value]);

    $this->supplier = Supplier::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->brand = Brand::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
    ]);

    $this->category = Category::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->template = Template::factory()->create([
        'organization_id' => $this->organization->id,
        'category_id' => $this->category->id,
    ]);

    $this->product = Product::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $this->category->id,
        'template_id' => $this->template->id,
        'status' => ProductStatus::UnderReview,
    ]);

    $this->actingAs($this->systemAdmin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('allows system admins to approve a product from the admin review page', function () {
    Livewire::test(EditAdminProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertActionVisible('approve')
        ->callAction('approve')
        ->assertNotified();

    expect($this->product->fresh()->status)->toBe(ProductStatus::Approved);
});

it('allows system admins to reject a product from the admin review page', function () {
    Livewire::test(EditAdminProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertActionVisible('reject')
        ->callAction('reject')
        ->assertNotified();

    expect($this->product->fresh()->status)->toBe(ProductStatus::Rejected);
});

it('allows system admins to request clarification with a note from the admin review page', function () {
    Livewire::test(EditAdminProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertActionVisible('requestClarification')
        ->callAction('requestClarification', data: [
            'clarification_note' => 'Please upload the CE marking certificate.',
        ])
        ->assertNotified();

    $product = $this->product->fresh();

    expect($product->status)->toBe(ProductStatus::ClarificationNeeded)
        ->and($product->clarification_note)->toBe('Please upload the CE marking certificate.');
});

it('hides admin review decision actions when the product is not under review', function () {
    $this->product->update([
        'status' => ProductStatus::Open,
    ]);

    Livewire::test(EditAdminProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertActionHidden('approve')
        ->assertActionHidden('reject')
        ->assertActionHidden('requestClarification');
});

it('reuses the product relation managers in the admin product resource', function () {
    $relations = AdminProductResource::getRelations();

    expect($relations)
        ->toHaveKey('documents', DocumentsRelationManager::class)
        ->toHaveKey('safetyEntries', SafetyEntriesRelationManager::class);
});

it('shows relation manager content for documents and safety information on the admin review page', function () {
    $this->template->update([
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => ['safety_text', 'warning_text'],
    ]);

    $document = Document::factory()->withFile('manual.pdf')->create([
        'organization_id' => $this->organization->id,
        'product_id' => $this->product->id,
        'type' => DocumentType::Manual,
    ]);

    ProductSafetyEntry::factory()->create([
        'organization_id' => $this->organization->id,
        'product_id' => $this->product->id,
        'safety_text' => 'Keep away from direct heat.',
        'warning_text' => 'Adult supervision required.',
    ]);

    Livewire::test(EditAdminProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertSee('Documents')
        ->assertSee('manual.pdf')
        ->assertSee($document->getFirstMediaUrl(Document::FILE_COLLECTION), false)
        ->assertSee('Safety information')
        ->assertSee('Complete');
});

it('clears the clarification note when the product is resubmitted for review', function () {
    $this->template->update([
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => [],
    ]);

    Document::factory()->withFile('manual.pdf')->create([
        'organization_id' => $this->organization->id,
        'product_id' => $this->product->id,
        'type' => DocumentType::Manual,
    ]);

    $this->product->update([
        'category_id' => $this->category->id,
        'template_id' => $this->template->id,
        'status' => ProductStatus::ClarificationNeeded,
        'clarification_note' => 'Please provide CE certificate.',
        'last_reviewed_at' => now()->subDay(),
    ]);

    $this->product->touch();

    expect($this->product->fresh()->clarification_note)->toBe('Please provide CE certificate.');

    $this->product->fresh()->submitForReview();

    $product = $this->product->fresh();

    expect($product->status)->toBe(ProductStatus::UnderReview)
        ->and($product->clarification_note)->toBeNull();
});

it('requires the clarification note field when requesting clarification', function () {
    Livewire::test(EditAdminProduct::class, ['record' => $this->product->getRouteKey()])
        ->callAction('requestClarification', data: [
            'clarification_note' => '',
        ])
        ->assertHasActionErrors(['clarification_note' => 'required']);

    expect($this->product->fresh()->status)->toBe(ProductStatus::UnderReview);
});

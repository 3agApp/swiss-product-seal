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

it('allows system admins to request clarification from the admin review page', function () {
    Livewire::test(EditAdminProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertActionVisible('requestClarification')
        ->callAction('requestClarification')
        ->assertNotified();

    expect($this->product->fresh()->status)->toBe(ProductStatus::ClarificationNeeded);
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

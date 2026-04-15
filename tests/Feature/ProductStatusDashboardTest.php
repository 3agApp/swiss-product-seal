<?php

use App\Enums\DocumentType;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = User::factory()->create();
    $this->supplier = Supplier::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->brand = Brand::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $this->product = Product::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'status' => ProductStatus::UnderReview,
    ]);

    $this->organization->members()->attach($this->owner, ['role' => Role::Owner->value]);

    $this->actingAs($this->owner);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($this->organization);
});

it('does not expose a status field on the create product page', function () {
    Livewire::test(CreateProduct::class)
        ->assertFormFieldDoesNotExist('status');
});

it('does not show a selected category summary on the create product page', function () {
    Livewire::test(CreateProduct::class)
        ->assertDontSee('Selected category');
});

it('does not expose a status field on the edit product page', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertFormFieldDoesNotExist('status');
});

it('does not show a selected category summary on the edit product page', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertDontSee('Selected category');
});

it('shows the completeness score on the edit product page', function () {
    $category = Category::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $template = Template::factory()->create([
        'organization_id' => $this->organization->id,
        'category_id' => $category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => ['safety_text'],
    ]);

    $product = Product::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
    ]);

    Document::factory()->create([
        'organization_id' => $this->organization->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
    ]);

    ProductSafetyEntry::factory()->create([
        'organization_id' => $this->organization->id,
        'product_id' => $product->id,
        'safety_text' => null,
    ]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertSee('Completeness score')
        ->assertSee('50% complete')
        ->assertSee('1 of 2 required items are present.', false)
        ->assertSee('Missing required safety fields: Safety text.');
});

it('ignores dashboard attempts to change product status when editing', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->set('data.name', 'Updated Product Name')
        ->set('data.status', ProductStatus::Rejected->value)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->product->fresh()->name)->toBe('Updated Product Name')
        ->and($this->product->fresh()->status)->toBe(ProductStatus::UnderReview);
});

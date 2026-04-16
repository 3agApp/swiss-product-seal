<?php

use App\Enums\DocumentType;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Product;
use App\Models\ProductSafetyEntry;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->distributor = Distributor::factory()->create();
    $this->owner = User::factory()->create();
    $this->supplier = Supplier::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);
    $this->brand = Brand::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $this->product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'status' => ProductStatus::UnderReview,
    ]);

    $this->distributor->members()->attach($this->owner, ['role' => Role::Owner->value]);

    $this->actingAs($this->owner);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($this->distributor);
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

it('shows the current status on the edit product page', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertSee('Current status')
        ->assertSee('Under review');
});

it('does not show a selected category summary on the edit product page', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertDontSee('Selected category');
});

it('shows the completeness score on the edit product page', function () {
    $category = Category::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $template = Template::factory()->create([
        'distributor_id' => $this->distributor->id,
        'category_id' => $category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => ['safety_text'],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
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

it('submits a completed product for review from the edit page header action', function () {
    $category = Category::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $template = Template::factory()->create([
        'distributor_id' => $this->distributor->id,
        'category_id' => $category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => [],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::Open,
    ]);

    Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
    ]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertActionVisible('submitForReview')
        ->callAction('submitForReview')
        ->assertNotified();

    expect($product->fresh()->status)->toBe(ProductStatus::UnderReview);
});

it('hides the submit for review action on the edit page for incomplete products', function () {
    $category = Category::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $template = Template::factory()->create([
        'distributor_id' => $this->distributor->id,
        'category_id' => $category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => [],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::Open,
    ]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertActionHidden('submitForReview');
});

it('submits completed products for review from the table action', function () {
    $category = Category::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $template = Template::factory()->create([
        'distributor_id' => $this->distributor->id,
        'category_id' => $category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => [],
    ]);

    $product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::Open,
    ]);

    Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual,
    ]);

    Livewire::test(ListProducts::class)
        ->assertTableActionVisible('submitForReview', $product->fresh())
        ->callTableAction('submitForReview', $product->fresh());

    expect($product->fresh()->status)->toBe(ProductStatus::UnderReview);
});

it('hides the submit for review table action for incomplete products', function () {
    Livewire::test(ListProducts::class)
        ->assertTableActionHidden('submitForReview', $this->product->fresh());
});

it('hides secondary product table columns by default', function () {
    Livewire::test(ListProducts::class)
        ->assertTableColumnVisible('name')
        ->assertTableColumnVisible('category.name')
        ->assertTableColumnVisible('status')
        ->assertTableColumnVisible('completeness_score')
        ->assertTableColumnExists('template.name', fn ($column): bool => $column->isToggleable() && $column->isToggledHiddenByDefault())
        ->assertTableColumnExists('supplier.name', fn ($column): bool => $column->isToggleable() && $column->isToggledHiddenByDefault())
        ->assertTableColumnExists('brand.name', fn ($column): bool => $column->isToggleable() && $column->isToggledHiddenByDefault());
});

it('submits only eligible products from the bulk review action', function () {
    $category = Category::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $template = Template::factory()->create([
        'distributor_id' => $this->distributor->id,
        'category_id' => $category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => [],
    ]);

    $completedProduct = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::Open,
    ]);

    $incompleteProduct = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::Open,
    ]);

    Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $completedProduct->id,
        'type' => DocumentType::Manual,
    ]);

    Livewire::test(ListProducts::class)
        ->assertTableBulkActionExists('submitForReview')
        ->callTableBulkAction('submitForReview', [$completedProduct->fresh(), $incompleteProduct->fresh()]);

    expect($completedProduct->fresh()->status)->toBe(ProductStatus::UnderReview)
        ->and($incompleteProduct->fresh()->status)->toBe(ProductStatus::Open);
});

it('filters the products table with product tabs', function () {
    $category = Category::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $template = Template::factory()->create([
        'distributor_id' => $this->distributor->id,
        'category_id' => $category->id,
        'required_document_types' => [DocumentType::Manual->value],
        'required_data_fields' => [],
    ]);

    $completedProduct = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::Open,
        'name' => 'Completed product',
    ]);

    $underReviewProduct = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::UnderReview,
        'name' => 'Under review product',
    ]);

    $incompleteProduct = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
        'status' => ProductStatus::Open,
        'name' => 'Incomplete product',
    ]);

    Document::factory()->create([
        'distributor_id' => $this->distributor->id,
        'product_id' => $completedProduct->id,
        'type' => DocumentType::Manual,
    ]);

    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords([$completedProduct, $underReviewProduct, $incompleteProduct])
        ->set('activeTab', 'completed')
        ->assertCanSeeTableRecords([$completedProduct])
        ->assertCanNotSeeTableRecords([$underReviewProduct, $incompleteProduct])
        ->set('activeTab', 'under_review')
        ->assertCanSeeTableRecords([$underReviewProduct])
        ->assertCanNotSeeTableRecords([$completedProduct, $incompleteProduct])
        ->set('activeTab', 'incomplete')
        ->assertCanSeeTableRecords([$underReviewProduct, $incompleteProduct])
        ->assertCanNotSeeTableRecords([$completedProduct]);
});

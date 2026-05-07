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

it('combines the edit form and compliance sections into tabs', function () {
    $page = Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertSee('Basics')
        ->assertSee('Documents')
        ->assertSee('Safety information');

    expect($page->instance()->hasCombinedRelationManagerTabsWithContent())->toBeTrue()
        ->and($page->instance()->getContentTabLabel())->toBe('Basics');
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
    $category = Category::factory()->create();

    $template = Template::factory()->create([
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

it('allows creating a supplier inline from the product edit form', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->assertFormComponentActionVisible('supplier_id', 'createOption')
        ->callFormComponentAction('supplier_id', 'createOption', data: [
            'supplier_code' => 'SUP-NEW-001',
            'name' => 'New inline supplier',
            'email' => 'inline-supplier@example.com',
            'phone' => '+1-555-0100',
            'country' => 'Germany',
            'address' => 'Example Street 1',
            'active' => true,
        ])
        ->assertHasNoFormComponentActionErrors();

    $supplier = Supplier::query()
        ->where('distributor_id', $this->distributor->id)
        ->where('name', 'New inline supplier')
        ->first();

    expect($supplier)->not->toBeNull();

    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->callFormComponentAction('supplier_id', 'createOption', data: [
            'supplier_code' => 'SUP-NEW-002',
            'name' => 'Newest inline supplier',
            'email' => 'newest-inline-supplier@example.com',
            'phone' => '+1-555-0101',
            'country' => 'Germany',
            'address' => 'Example Street 2',
            'active' => true,
        ])
        ->assertSet('data.supplier_id', Supplier::query()
            ->where('distributor_id', $this->distributor->id)
            ->where('name', 'Newest inline supplier')
            ->value('id'));
});

it('rejects duplicate supplier codes in the inline create form', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->callFormComponentAction('supplier_id', 'createOption', data: [
            'supplier_code' => $this->supplier->supplier_code,
            'name' => 'Different supplier name',
            'email' => 'duplicate-code@example.com',
            'phone' => '+1-555-0102',
            'country' => 'Germany',
            'address' => 'Example Street 3',
            'active' => true,
        ])
        ->assertHasFormComponentActionErrors(['supplier_code' => 'unique']);
});

it('rejects duplicate supplier names in the inline create form', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->callFormComponentAction('supplier_id', 'createOption', data: [
            'supplier_code' => 'SUP-NEW-003',
            'name' => $this->supplier->name,
            'email' => 'duplicate-name@example.com',
            'phone' => '+1-555-0103',
            'country' => 'Germany',
            'address' => 'Example Street 4',
            'active' => true,
        ])
        ->assertHasFormComponentActionErrors(['name' => 'unique']);
});

it('allows creating a brand inline for the selected supplier from the product edit form', function () {
    $page = Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->set('data.supplier_id', $this->supplier->id)
        ->assertFormComponentActionVisible('brand_id', 'createOption')
        ->callFormComponentAction('brand_id', 'createOption', data: [
            'name' => 'Inline product brand',
        ])
        ->assertHasNoFormComponentActionErrors();

    $brand = Brand::query()
        ->where('distributor_id', $this->distributor->id)
        ->where('supplier_id', $this->supplier->id)
        ->where('name', 'Inline product brand')
        ->first();

    expect($brand)->not->toBeNull();

    $page->assertSet('data.brand_id', $brand->id);
});

it('rejects duplicate brand names for the selected supplier in the inline create form', function () {
    Livewire::test(EditProduct::class, ['record' => $this->product->getRouteKey()])
        ->set('data.supplier_id', $this->supplier->id)
        ->callFormComponentAction('brand_id', 'createOption', data: [
            'name' => $this->brand->name,
        ])
        ->assertHasFormComponentActionErrors(['name' => 'unique']);
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
    $category = Category::factory()->create();

    $template = Template::factory()->create([
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
    $category = Category::factory()->create();

    $template = Template::factory()->create([
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
    $category = Category::factory()->create();

    $template = Template::factory()->create([
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
    $category = Category::factory()->create();

    $template = Template::factory()->create([
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
    $category = Category::factory()->create();

    $template = Template::factory()->create([
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

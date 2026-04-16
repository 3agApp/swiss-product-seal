<?php

use App\Enums\Role;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\RelationManagers\SafetyEntriesRelationManager;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductSafetyEntry;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

test('product safety entries table has a unique constraint for product', function () {
    $indexes = collect(Schema::getIndexes('product_safety_entries'));

    expect($indexes->contains(function (array $index): bool {
        return $index['unique']
            && $index['columns'] === ['product_id'];
    }))->toBeTrue();
});

test('product safety entries inherit the owning product distributor when created through the relation', function () {
    $distributor = Distributor::factory()->create();
    $supplier = Supplier::factory()->create([
        'distributor_id' => $distributor->id,
    ]);
    $brand = Brand::factory()->create([
        'distributor_id' => $distributor->id,
        'supplier_id' => $supplier->id,
    ]);
    $product = Product::factory()->create([
        'distributor_id' => $distributor->id,
        'supplier_id' => $supplier->id,
        'brand_id' => $brand->id,
    ]);

    $entry = ProductSafetyEntry::query()->create([
        'product_id' => $product->id,
        'warning_text' => 'Adult supervision required.',
    ]);

    expect($entry->distributor_id)->toBe($distributor->id)
        ->and($entry->product->is($product))->toBeTrue();
});

test('product resource registers the safety information relation manager', function () {
    $distributor = Distributor::factory()->create(['slug' => 'acme-corp']);
    $owner = User::factory()->create();
    $distributor->members()->attach($owner, ['role' => Role::Owner->value]);

    $supplier = Supplier::factory()->create([
        'distributor_id' => $distributor->id,
    ]);
    $brand = Brand::factory()->create([
        'distributor_id' => $distributor->id,
        'supplier_id' => $supplier->id,
    ]);
    $product = Product::factory()->create([
        'distributor_id' => $distributor->id,
        'supplier_id' => $supplier->id,
        'brand_id' => $brand->id,
    ]);

    ProductSafetyEntry::factory()->create([
        'distributor_id' => $distributor->id,
        'product_id' => $product->id,
        'safety_text' => 'Keep away from open flames.',
    ]);

    $relations = ProductResource::getRelations();
    $safetyEntries = $product->safetyEntries();

    expect($relations)->toHaveKey('safetyEntries', SafetyEntriesRelationManager::class)
        ->and($safetyEntries)->toBeInstanceOf(HasMany::class)
        ->and($safetyEntries->getRelated())->toBeInstanceOf(ProductSafetyEntry::class)
        ->and(SafetyEntriesRelationManager::getTitle($product, EditProduct::class))->toBe('Safety information')
        ->and(SafetyEntriesRelationManager::canViewForRecord($product, EditProduct::class))->toBeTrue();
});

test('product safety entries report missing template-required fields', function () {
    $distributor = Distributor::factory()->create();
    $category = Category::factory()->create([
        'distributor_id' => $distributor->id,
    ]);
    $template = Template::factory()->create([
        'distributor_id' => $distributor->id,
        'category_id' => $category->id,
        'required_data_fields' => ['safety_text', 'warning_text', 'age_grading'],
    ]);
    $product = Product::factory()->create([
        'distributor_id' => $distributor->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
    ]);

    $entry = ProductSafetyEntry::factory()->create([
        'distributor_id' => $distributor->id,
        'product_id' => $product->id,
        'safety_text' => 'Keep away from heat sources.',
        'warning_text' => null,
        'age_grading' => null,
    ]);

    expect($entry->templateCompletionStatus())->toBe('Incomplete')
        ->and($entry->requiredTemplateFieldCount())->toBe(3)
        ->and($entry->completedRequiredTemplateFieldCount())->toBe(1)
        ->and($entry->missingRequiredTemplateFields())->toBe(['Warning text', 'Age grading'])
        ->and($entry->templateCompletionSummary())->toBe('1 of 3 required safety fields are filled.')
        ->and($entry->missingRequiredTemplateFieldsSummary())->toBe('Warning text, Age grading');
});

test('product safety entries report when the template has no required safety fields', function () {
    $distributor = Distributor::factory()->create();
    $category = Category::factory()->create([
        'distributor_id' => $distributor->id,
    ]);
    $template = Template::factory()->create([
        'distributor_id' => $distributor->id,
        'category_id' => $category->id,
        'required_data_fields' => [],
    ]);
    $product = Product::factory()->create([
        'distributor_id' => $distributor->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
    ]);

    $entry = ProductSafetyEntry::factory()->create([
        'distributor_id' => $distributor->id,
        'product_id' => $product->id,
    ]);

    expect($entry->templateCompletionStatus())->toBe('Not required')
        ->and($entry->requiredTemplateFieldCount())->toBe(0)
        ->and($entry->completedRequiredTemplateFieldCount())->toBe(0)
        ->and($entry->requiredTemplateFieldsSummary())->toBe('No safety fields required by the assigned template.')
        ->and($entry->missingRequiredTemplateFieldsSummary())->toBe('Nothing missing.');
});

test('product safety entry form helper text marks template-required fields without making others required', function () {
    $distributor = Distributor::factory()->create();
    $category = Category::factory()->create([
        'distributor_id' => $distributor->id,
    ]);
    $template = Template::factory()->create([
        'distributor_id' => $distributor->id,
        'category_id' => $category->id,
        'required_data_fields' => ['warning_text', 'safety_instructions'],
    ]);
    $product = Product::factory()->create([
        'distributor_id' => $distributor->id,
        'category_id' => $category->id,
        'template_id' => $template->id,
    ]);

    expect(ProductSafetyEntry::templateFieldHelperTextForProduct($product, 'warning_text'))->toBe('Required by template')
        ->and(ProductSafetyEntry::templateFieldHelperTextForProduct($product, 'safety_instructions'))->toBe('Required by template')
        ->and(ProductSafetyEntry::templateFieldHelperTextForProduct($product, 'safety_text'))->toBeNull()
        ->and(ProductSafetyEntry::isTemplateFieldRequiredForProduct($product, 'warning_text'))->toBeTrue()
        ->and(ProductSafetyEntry::isTemplateFieldRequiredForProduct($product, 'safety_text'))->toBeFalse();
});

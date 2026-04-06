<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('displays the products index page', function () {
    Product::factory()->count(3)->create();

    $this->get(route('products.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('products/index'));
});

it('redirects guests to login from products index', function () {
    auth()->logout();

    $this->get(route('products.index'))
        ->assertRedirect(route('login'));
});

it('displays the create product form', function () {
    $this->get(route('products.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('products/create'));
});

it('stores a new product', function () {
    $supplier = Supplier::factory()->create();
    $brand = Brand::factory()->for($supplier)->create();

    $this->post(route('products.store'), [
        'name' => 'Test Product',
        'internal_article_number' => 'INT-99999',
        'supplier_article_number' => 'SUP-99999',
        'order_number' => 'ORD-99999',
        'ean' => '4006381333931',
        'supplier_id' => $supplier->id,
        'brand_id' => $brand->id,
        'status' => ProductStatus::Open->value,
        'kontor_id' => 'KON-0001',
    ])->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'internal_article_number' => 'INT-99999',
        'ean' => '4006381333931',
    ]);
});

it('validates required fields on store', function () {
    $this->post(route('products.store'), [])
        ->assertSessionHasErrors(['name']);
});

it('stores a product without supplier and brand', function () {
    $this->post(route('products.store'), [
        'name' => 'Standalone Product',
        'supplier_id' => '',
        'brand_id' => '',
        'status' => ProductStatus::Open->value,
    ])->assertRedirect(route('products.index'));

    expect(Product::where('name', 'Standalone Product')->first())
        ->supplier_id->toBeNull()
        ->brand_id->toBeNull();
});

it('validates supplier exists on store', function () {
    $this->post(route('products.store'), [
        'name' => 'Test Product',
        'supplier_id' => 99999,
    ])->assertSessionHasErrors(['supplier_id']);
});

it('displays the edit product form', function () {
    $product = Product::factory()->create();

    $this->get(route('products.edit', $product))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('products/edit'));
});

it('updates an existing product', function () {
    $product = Product::factory()->create();

    $this->put(route('products.update', $product), [
        'name' => 'Updated Product Name',
        'ean' => '1234567890123',
        'status' => ProductStatus::InProgress->value,
    ])->assertRedirect(route('products.index'));

    expect($product->fresh())
        ->name->toBe('Updated Product Name')
        ->ean->toBe('1234567890123')
        ->status->toBe(ProductStatus::InProgress);
});

it('validates required fields on update', function () {
    $product = Product::factory()->create();

    $this->put(route('products.update', $product), [])
        ->assertSessionHasErrors(['name']);
});

it('deletes a product', function () {
    $product = Product::factory()->create();

    $this->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

it('filters products by search term', function () {
    Product::factory()->create(['name' => 'Widget Alpha']);
    Product::factory()->create(['name' => 'Gadget Beta']);

    $this->get(route('products.index', ['search' => 'Widget']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Widget Alpha')
        );
});

it('sorts products by column', function () {
    Product::factory()->create(['name' => 'Zebra Product']);
    Product::factory()->create(['name' => 'Alpha Product']);

    $this->get(route('products.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/index')
            ->where('products.data.0.name', 'Alpha Product')
            ->where('products.data.1.name', 'Zebra Product')
        );
});

it('ignores invalid sort columns', function () {
    Product::factory()->count(2)->create();

    $this->get(route('products.index', ['sort' => 'DROP TABLE products']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/index')
            ->where('filters.sort', '')
        );
});

it('preserves active filters in product pagination links', function () {
    Product::factory()
        ->count(20)
        ->sequence(fn (Sequence $sequence) => [
            'name' => "Test Product {$sequence->index}",
        ])
        ->create();

    $response = $this->get(route('products.index', [
        'search' => 'Test',
        'sort' => 'name',
        'direction' => 'asc',
        'page' => 2,
    ]));

    $response
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/index')
            ->where('products.current_page', 2)
            ->where('filters.search', 'Test')
            ->where('filters.sort', 'name')
            ->where('filters.direction', 'asc')
        );

    collect($response->inertiaProps('products.links'))
        ->pluck('url')
        ->filter()
        ->each(function (string $url) {
            expect($url)
                ->toContain('search=Test')
                ->toContain('sort=name')
                ->toContain('direction=asc');
        });
});

it('stores a product with an image', function () {
    Storage::fake('public');

    $this->post(route('products.store'), [
        'name' => 'Product With Image',
        'status' => ProductStatus::Open->value,
        'image' => UploadedFile::fake()->image('product.jpg', 640, 480),
    ])->assertRedirect(route('products.index'));

    $product = Product::where('name', 'Product With Image')->first();

    expect($product->getFirstMediaUrl('image'))->not->toBeEmpty();
});

it('updates a product with a new image', function () {
    Storage::fake('public');

    $product = Product::factory()->create();

    $this->put(route('products.update', $product), [
        'name' => $product->name,
        'status' => ProductStatus::Open->value,
        'image' => UploadedFile::fake()->image('updated.png', 800, 600),
    ])->assertRedirect(route('products.index'));

    expect($product->fresh()->getFirstMediaUrl('image'))->not->toBeEmpty();
});

it('validates image file type on store', function () {
    $this->post(route('products.store'), [
        'name' => 'Bad Image Product',
        'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors(['image']);
});

it('includes image_url in products index data', function () {
    Storage::fake('public');

    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->image('test.jpg'))->toMediaCollection('image');

    $this->get(route('products.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/index')
            ->where('products.data.0.image_url', fn ($value) => str_contains($value, 'test'))
        );
});

it('includes image_url in product edit data', function () {
    Storage::fake('public');

    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->image('edit-test.jpg'))->toMediaCollection('image');

    $this->get(route('products.edit', $product))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/edit')
            ->where('product.image_url', fn ($value) => str_contains($value, 'edit-test'))
        );
});

it('removes the product image when remove_image is set', function () {
    Storage::fake('public');

    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->image('to-remove.jpg'))->toMediaCollection('image');

    expect($product->getFirstMediaUrl('image'))->not->toBeEmpty();

    $this->put(route('products.update', $product), [
        'name' => $product->name,
        'remove_image' => '1',
    ])->assertRedirect(route('products.index'));

    expect($product->fresh()->getFirstMediaUrl('image'))->toBeEmpty();
});

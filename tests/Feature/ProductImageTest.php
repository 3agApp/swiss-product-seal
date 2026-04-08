<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('uploads images to a product', function () {
    $product = Product::factory()->create();

    $this->postJson(route('products.images.store', $product), [
        'images' => [
            UploadedFile::fake()->image('photo1.jpg', 640, 480),
            UploadedFile::fake()->image('photo2.png', 800, 600),
        ],
    ])
        ->assertSuccessful()
        ->assertJsonCount(2, 'images')
        ->assertJsonStructure(['images' => [['id', 'url', 'preview_url', 'name', 'order']]]);

    expect($product->getMedia('images'))->toHaveCount(2);
});

it('limits products to 10 images', function () {
    $product = Product::factory()->create();

    foreach (range(1, 10) as $i) {
        $product->addMedia(UploadedFile::fake()->image("img{$i}.jpg"))
            ->toMediaCollection('images');
    }

    $this->postJson(route('products.images.store', $product), [
        'images' => [UploadedFile::fake()->image('extra.jpg')],
    ])->assertStatus(422);

    expect($product->getMedia('images'))->toHaveCount(10);
});

it('validates image file types', function () {
    $product = Product::factory()->create();

    $this->postJson(route('products.images.store', $product), [
        'images' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
    ])->assertStatus(422);
});

it('validates images are required', function () {
    $product = Product::factory()->create();

    $this->postJson(route('products.images.store', $product), [
        'images' => [],
    ])->assertStatus(422);
});

it('removes an image from a product', function () {
    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->image('to-delete.jpg'))
        ->toMediaCollection('images');

    $media = $product->getFirstMedia('images');

    $this->deleteJson(route('products.images.destroy', [$product, $media]))
        ->assertSuccessful()
        ->assertJsonCount(0, 'images');

    expect($product->fresh()->getMedia('images'))->toHaveCount(0);
});

it('prevents deleting media from another product', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $product2->addMedia(UploadedFile::fake()->image('other.jpg'))
        ->toMediaCollection('images');

    $media = $product2->getFirstMedia('images');

    $this->deleteJson(route('products.images.destroy', [$product1, $media]))
        ->assertNotFound();
});

it('reorders product images', function () {
    $product = Product::factory()->create();

    $product->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('images');
    $product->addMedia(UploadedFile::fake()->image('second.jpg'))->toMediaCollection('images');
    $product->addMedia(UploadedFile::fake()->image('third.jpg'))->toMediaCollection('images');

    $media = $product->getMedia('images');
    $reversedIds = $media->reverse()->pluck('id')->values()->all();

    $this->putJson(route('products.images.reorder', $product), [
        'ids' => $reversedIds,
    ])
        ->assertSuccessful()
        ->assertJsonCount(3, 'images');

    $reordered = $product->fresh()->getMedia('images');
    expect($reordered->first()->file_name)->toBe('third.jpg');
    expect($reordered->last()->file_name)->toBe('first.jpg');
});

it('rejects reorder requests that include images from another product', function () {
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();

    $product->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('images');
    $product->addMedia(UploadedFile::fake()->image('second.jpg'))->toMediaCollection('images');
    $otherProduct->addMedia(UploadedFile::fake()->image('foreign.jpg'))->toMediaCollection('images');

    $requestedIds = [
        $product->getMedia('images')[1]->id,
        $otherProduct->getFirstMedia('images')->id,
    ];

    $this->putJson(route('products.images.reorder', $product), [
        'ids' => $requestedIds,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ids']);
});

it('requires authentication for image operations', function () {
    auth()->logout();
    $product = Product::factory()->create();

    $this->postJson(route('products.images.store', $product), [
        'images' => [UploadedFile::fake()->image('test.jpg')],
    ])->assertUnauthorized();

    $this->deleteJson(route('products.images.destroy', [$product, 1]))
        ->assertUnauthorized();

    $this->putJson(route('products.images.reorder', $product), ['ids' => [1]])
        ->assertUnauthorized();
});

it('includes images in product edit data', function () {
    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->image('edit-img.jpg'))
        ->toMediaCollection('images');

    $this->get(route('products.edit', $product))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('products/edit')
            ->has('product.images', 1)
            ->where('product.images.0.name', 'edit-img.jpg')
        );
});

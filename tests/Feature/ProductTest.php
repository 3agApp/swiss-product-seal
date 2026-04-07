<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

it('requires a name', function () {
    expect(fn () => Product::create())->toThrow(QueryException::class);
});

it('allows nullable product fields to be omitted', function () {
    $product = Product::create([
        'name' => 'Test Product',
    ]);

    $this->assertModelExists($product);

    expect($product->internal_article_number)->toBeNull()
        ->and($product->supplier_article_number)->toBeNull()
        ->and($product->order_number)->toBeNull()
        ->and($product->ean)->toBeNull()
        ->and($product->supplier_id)->toBeNull()
        ->and($product->brand_id)->toBeNull()
        ->and($product->status)->toBe(ProductStatus::Open)
        ->and($product->kontor_id)->toBeNull()
        ->and($product->source_last_sync_at)->toBeNull()
        ->and($product->public_uuid)->not->toBeEmpty();
});

it('belongs to a supplier and brand', function () {
    $supplier = Supplier::factory()->create();
    $brand = Brand::factory()->for($supplier)->create();

    $product = Product::create([
        'name' => 'Compliance Widget',
        'supplier_id' => $supplier->id,
        'brand_id' => $brand->id,
    ]);

    expect($product->supplier->is($supplier))->toBeTrue()
        ->and($product->brand->is($brand))->toBeTrue()
        ->and($supplier->products()->first()?->is($product))->toBeTrue()
        ->and($brand->products()->first()?->is($product))->toBeTrue();
});

it('casts product attributes', function () {
    $supplier = Supplier::factory()->create();
    $brand = Brand::factory()->for($supplier)->create();

    $product = Product::create([
        'name' => 'Tracked Product',
        'supplier_id' => $supplier->id,
        'brand_id' => $brand->id,
        'status' => ProductStatus::Submitted->value,
        'source_last_sync_at' => '2026-04-04 12:30:00',
    ])->fresh();

    expect($product->supplier_id)->toBeInt()
        ->and($product->brand_id)->toBeInt()
        ->and($product->status)->toBe(ProductStatus::Submitted)
        ->and($product->source_last_sync_at)->toBeInstanceOf(CarbonInterface::class);
});

it('generates a public uuid automatically', function () {
    $product = Product::create([
        'name' => 'UUID Product',
    ]);

    expect(Str::isUuid($product->public_uuid))->toBeTrue();
});

it('provides labels and options for product statuses', function () {
    expect(ProductStatus::InProgress->label())->toBe('In progress')
        ->and(ProductStatus::ClarificationNeeded->label())->toBe('Clarification needed')
        ->and(ProductStatus::options())->toBe([
            'open' => 'Open',
            'in_progress' => 'In progress',
            'submitted' => 'Submitted',
            'under_review' => 'Under review',
            'clarification_needed' => 'Clarification needed',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
        ]);
});

it('nulls nullable foreign keys when the supplier or brand is deleted', function () {
    $supplier = Supplier::factory()->create();
    $brand = Brand::factory()->for($supplier)->create();
    $product = Product::create([
        'name' => 'Linked Product',
        'supplier_id' => $supplier->id,
        'brand_id' => $brand->id,
    ]);

    $brand->delete();
    $supplier->delete();

    expect($product->fresh()?->supplier_id)->toBeNull()
        ->and($product->fresh()?->brand_id)->toBeNull();
});

it('stores multiple product images in the media collection', function () {
    Storage::fake('public');

    $product = Product::create([
        'name' => 'Imaged Product',
    ]);

    $firstImage = UploadedFile::fake()->image('first-image.jpg');
    $secondImage = UploadedFile::fake()->image('second-image.jpg');

    $product->addMedia($firstImage)->toMediaCollection('images');
    $product->addMedia($secondImage)->toMediaCollection('images');

    $product->refresh();

    expect($product->getMedia('images'))->toHaveCount(2)
        ->and($product->getFirstMedia('images')?->file_name)->toBe('first-image.jpg')
        ->and($product->getFirstMedia('images')?->disk)->toBe('public');
});

<?php

use App\Enums\ProductStatus;
use App\Models\Distributor;
use App\Models\Product;

it('displays the public product page for a valid public uuid', function () {
    $distributor = Distributor::factory()->create();
    $product = Product::factory()->create([
        'distributor_id' => $distributor->id,
        'name' => 'Test Product Alpha',
        'status' => ProductStatus::Approved,
    ]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertSee('Test Product Alpha')
        ->assertSee($distributor->name)
        ->assertSee('SPS_verified_trans.png', false);
});

it('shows in-progress seal for products under review', function () {
    $product = Product::factory()->create([
        'distributor_id' => Distributor::factory(),
        'status' => ProductStatus::UnderReview,
    ]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertSee('SPS_in_progress_trans.png', false);
});

it('shows not-verified seal for open products', function () {
    $product = Product::factory()->create([
        'distributor_id' => Distributor::factory(),
        'status' => ProductStatus::Open,
        'completeness_score' => 0,
    ]);

    $this->get(route('products.public', $product->public_uuid))
        ->assertSuccessful()
        ->assertSee('SPS_not_verified_trans.png', false);
});

it('returns 404 for an invalid public uuid', function () {
    $this->get('/p/non-existent-uuid')
        ->assertNotFound();
});

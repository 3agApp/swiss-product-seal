<?php

use App\Models\Brand;
use App\Models\Supplier;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->supplier = Supplier::factory()->create();
});

it('stores a brand for a supplier', function () {
    $this->post(route('suppliers.brands.store', $this->supplier), [
        'name' => 'New Brand',
    ])->assertRedirect();

    $this->assertDatabaseHas('brands', [
        'supplier_id' => $this->supplier->id,
        'name' => 'New Brand',
    ]);
});

it('validates brand name on store', function () {
    $this->post(route('suppliers.brands.store', $this->supplier), [])
        ->assertSessionHasErrors(['name']);
});

it('validates brand name minimum length on store', function () {
    $this->post(route('suppliers.brands.store', $this->supplier), [
        'name' => 'A',
    ])->assertSessionHasErrors(['name']);
});

it('updates a brand', function () {
    $brand = Brand::factory()->for($this->supplier)->create();

    $this->put(route('suppliers.brands.update', [$this->supplier, $brand]), [
        'name' => 'Updated Brand',
    ])->assertRedirect();

    expect($brand->fresh()->name)->toBe('Updated Brand');
});

it('validates brand name on update', function () {
    $brand = Brand::factory()->for($this->supplier)->create();

    $this->put(route('suppliers.brands.update', [$this->supplier, $brand]), [])
        ->assertSessionHasErrors(['name']);
});

it('deletes a brand', function () {
    $brand = Brand::factory()->for($this->supplier)->create();

    $this->delete(route('suppliers.brands.destroy', [$this->supplier, $brand]))
        ->assertRedirect();

    $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
});

it('redirects guests from brand routes', function () {
    auth()->logout();

    $brand = Brand::factory()->for($this->supplier)->create();

    $this->post(route('suppliers.brands.store', $this->supplier), ['name' => 'Test'])
        ->assertRedirect(route('login'));

    $this->put(route('suppliers.brands.update', [$this->supplier, $brand]), ['name' => 'Test'])
        ->assertRedirect(route('login'));

    $this->delete(route('suppliers.brands.destroy', [$this->supplier, $brand]))
        ->assertRedirect(route('login'));
});

it('loads brands on supplier edit page', function () {
    Brand::factory()->for($this->supplier)->count(3)->create();

    $this->get(route('suppliers.edit', $this->supplier))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('suppliers/edit')
            ->has('supplier.brands', 3)
        );
});

it('rejects duplicate brand name for the same supplier', function () {
    Brand::factory()->for($this->supplier)->create(['name' => 'Existing Brand']);

    $this->post(route('suppliers.brands.store', $this->supplier), [
        'name' => 'Existing Brand',
    ])->assertSessionHasErrors(['name']);
});

it('allows same brand name for different suppliers', function () {
    $otherSupplier = Supplier::factory()->create();
    Brand::factory()->for($otherSupplier)->create(['name' => 'Shared Name']);

    $this->post(route('suppliers.brands.store', $this->supplier), [
        'name' => 'Shared Name',
    ])->assertRedirect();

    $this->assertDatabaseHas('brands', [
        'supplier_id' => $this->supplier->id,
        'name' => 'Shared Name',
    ]);
});

it('allows updating brand to keep its own name', function () {
    $brand = Brand::factory()->for($this->supplier)->create(['name' => 'My Brand']);

    $this->put(route('suppliers.brands.update', [$this->supplier, $brand]), [
        'name' => 'My Brand',
    ])->assertRedirect();
});

it('rejects updating brand to an existing name for the same supplier', function () {
    Brand::factory()->for($this->supplier)->create(['name' => 'Taken Name']);
    $brand = Brand::factory()->for($this->supplier)->create(['name' => 'Other Brand']);

    $this->put(route('suppliers.brands.update', [$this->supplier, $brand]), [
        'name' => 'Taken Name',
    ])->assertSessionHasErrors(['name']);
});

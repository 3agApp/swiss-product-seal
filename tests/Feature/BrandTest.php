<?php

use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Database\QueryException;

it('requires a supplier and name', function () {
    expect(fn () => Brand::create([
        'name' => 'Acme Basic',
    ]))->toThrow(QueryException::class);

    expect(fn () => Brand::create([
        'supplier_id' => Supplier::factory()->create()->id,
    ]))->toThrow(QueryException::class);
});

it('belongs to a supplier', function () {
    $supplier = Supplier::factory()->create();

    $brand = Brand::create([
        'supplier_id' => $supplier->id,
        'name' => 'Acme Basic',
    ]);

    $this->assertModelExists($brand);

    expect($brand->supplier->is($supplier))->toBeTrue()
        ->and($supplier->brands()->first()?->is($brand))->toBeTrue();
});

it('casts supplier id to an integer', function () {
    $supplier = Supplier::factory()->create();

    $brand = Brand::factory()->for($supplier)->create()->fresh();

    expect($brand->supplier_id)
        ->toBeInt()
        ->toBe($supplier->id);
});

it('deletes brands when the supplier is deleted', function () {
    $supplier = Supplier::factory()->create();
    $brand = Brand::factory()->for($supplier)->create();

    $supplier->delete();

    $this->assertModelMissing($brand);
});

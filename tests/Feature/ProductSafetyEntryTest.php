<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('product safety entries table has a unique constraint for product', function () {
    $indexes = collect(Schema::getIndexes('product_safety_entries'));

    expect($indexes->contains(function (array $index): bool {
        return $index['unique']
            && $index['columns'] === ['product_id'];
    }))->toBeTrue();
});

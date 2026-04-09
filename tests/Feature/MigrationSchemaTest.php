<?php

use Illuminate\Support\Facades\Schema;

it('creates the consolidated schema for brands, products, and templates', function () {
    expect(Schema::hasColumns('brands', ['supplier_id', 'name']))->toBeTrue()
        ->and(Schema::hasColumn('products', 'template_id'))->toBeTrue()
        ->and(Schema::hasColumn('templates', 'required_data_fields'))->toBeTrue()
        ->and(Schema::hasColumn('templates', 'optional_document_types'))->toBeFalse();
});

<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSafetyEntry;
use Illuminate\Database\Seeder;

class ProductSafetyEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::all()->each(function (Product $product): void {
            ProductSafetyEntry::factory()
                ->for($product)
                ->create();
        });
    }
}

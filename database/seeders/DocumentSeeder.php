<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::all()->each(function (Product $product): void {
            Document::factory()
                ->count(fake()->numberBetween(1, 3))
                ->for($product)
                ->create();
        });
    }
}

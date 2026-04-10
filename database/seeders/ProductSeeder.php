<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\Template;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::with('brands')->get();
        $templates = Template::with('category')->get();

        $templates->each(function (Template $template) use ($suppliers): void {
            $supplier = $suppliers->random();
            $brand = $supplier->brands->isNotEmpty() ? $supplier->brands->random() : null;

            Product::factory()
                ->count(fake()->numberBetween(2, 5))
                ->for($supplier)
                ->for($template)
                ->for($template->category)
                ->state(fn (): array => [
                    'brand_id' => $brand?->id,
                ])
                ->create();
        });
    }
}

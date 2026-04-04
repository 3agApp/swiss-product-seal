<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'internal_article_number' => strtoupper(fake()->bothify('INT-#####')),
            'supplier_article_number' => strtoupper(fake()->bothify('SUP-#####')),
            'order_number' => strtoupper(fake()->bothify('ORD-#####')),
            'ean' => fake()->ean13(),
            'status' => ProductStatus::Open,
            'kontor_id' => fake()->bothify('KON-####'),
            'source_last_sync_at' => fake()->optional()->dateTime(),
        ];
    }
}

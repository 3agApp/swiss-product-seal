<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductSafetyEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSafetyEntry>
 */
class ProductSafetyEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'distributor_id' => Distributor::factory(),
            'product_id' => fn (array $attributes): int => Product::factory()->create([
                'distributor_id' => $attributes['distributor_id'],
            ])->id,
            'safety_text' => fake()->optional()->sentence(),
            'warning_text' => fake()->optional()->sentence(),
            'age_grading' => fake()->optional()->word(),
            'material_information' => fake()->optional()->sentence(),
            'usage_restrictions' => fake()->optional()->sentence(),
            'safety_instructions' => fake()->optional()->sentence(),
            'additional_notes' => fake()->optional()->sentence(),
        ];
    }
}

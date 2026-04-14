<?php

namespace Database\Factories;

use App\Models\Organization;
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
            'organization_id' => Organization::factory(),
            'product_id' => fn (array $attributes): int => Product::factory()->create([
                'organization_id' => $attributes['organization_id'],
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

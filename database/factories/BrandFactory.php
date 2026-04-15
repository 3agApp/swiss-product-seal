<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'supplier_id' => fn (array $attributes): int => Supplier::factory()->create([
                'organization_id' => $attributes['organization_id'],
            ])->id,
            'name' => fake()->company(),
        ];
    }
}

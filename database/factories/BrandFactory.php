<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Distributor;
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
            'distributor_id' => Distributor::factory(),
            'supplier_id' => fn (array $attributes): int => Supplier::factory()->create([
                'distributor_id' => $attributes['distributor_id'],
            ])->id,
            'name' => fake()->company(),
        ];
    }
}

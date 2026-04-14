<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'supplier_code' => strtoupper(fake()->bothify('SUP-#####')),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'country' => fake()->country(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'active' => fake()->boolean(),
            'kontor_id' => fake()->bothify('KON-####'),
        ];
    }
}

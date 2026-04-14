<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true),
            'internal_article_number' => strtoupper(fake()->bothify('INT-#####')),
            'supplier_article_number' => strtoupper(fake()->bothify('SUP-#####')),
            'order_number' => strtoupper(fake()->bothify('ORD-#####')),
            'ean' => fake()->ean13(),
            'category_id' => fn (array $attributes): int => Category::factory()->create([
                'organization_id' => $attributes['organization_id'],
            ])->id,
            'status' => ProductStatus::Open,
            'kontor_id' => fake()->bothify('KON-####'),
            'source_last_sync_at' => fake()->optional()->dateTime(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Product $product): void {
            if (! $product->template_id) {
                $template = Template::factory()->create([
                    'organization_id' => $product->organization_id,
                    'category_id' => $product->category_id,
                ]);
                $product->template_id = $template->id;
            }
        });
    }
}

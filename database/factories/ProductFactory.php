<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Distributor;
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
            'distributor_id' => Distributor::factory(),
            'name' => fake()->words(3, true),
            'internal_article_number' => strtoupper(fake()->bothify('INT-#####')),
            'supplier_article_number' => strtoupper(fake()->bothify('SUP-#####')),
            'order_number' => strtoupper(fake()->bothify('ORD-#####')),
            'ean' => fake()->ean13(),
            'category_id' => Category::factory(),
            'status' => ProductStatus::Open,
            'source_last_sync_at' => fake()->optional()->dateTime(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Product $product): void {
            if (! $product->template_id) {
                $template = Template::factory()->create([
                    'category_id' => $product->category_id,
                ]);
                $product->template_id = $template->id;
            }
        });
    }
}

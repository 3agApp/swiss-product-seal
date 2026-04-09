<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Template;
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
            'category_id' => Category::factory(),
            'status' => ProductStatus::Open,
            'kontor_id' => fake()->bothify('KON-####'),
            'source_last_sync_at' => fake()->optional()->dateTime(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Product $product): void {
            if (! $product->template_id) {
                $template = Template::factory()->create(['category_id' => $product->category_id]);
                $product->template_id = $template->id;
            }
        });
    }
}

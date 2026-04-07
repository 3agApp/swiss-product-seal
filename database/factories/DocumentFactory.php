<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => fake()->randomElement(DocumentType::cases()),
            'version' => 1,
            'expiry_date' => fake()->optional()->date('Y-m-d'),
            'review_comment' => fake()->optional()->sentence(),
            'is_current' => true,
        ];
    }

    public function replacementOf(Document $document): static
    {
        return $this->state(fn (): array => [
            'product_id' => $document->product_id,
            'type' => $document->type,
            'version_group_uuid' => $document->version_group_uuid,
            'replaces_document_id' => $document->id,
            'version' => $document->version + 1,
        ]);
    }
}

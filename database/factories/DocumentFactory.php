<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
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
            'type' => fake()->randomElement(DocumentType::cases()),
            'version' => 1,
            'expiry_date' => fake()->optional()->date('Y-m-d'),
            'review_comment' => fake()->optional()->sentence(),
            'is_current' => true,
            'public_download' => false,
        ];
    }

    public function publicDownload(): static
    {
        return $this->state(fn (): array => [
            'public_download' => true,
        ]);
    }

    public function replacementOf(Document $document): static
    {
        return $this->state(fn (): array => [
            'organization_id' => $document->organization_id,
            'product_id' => $document->product_id,
            'type' => $document->type,
            'version_group_uuid' => $document->version_group_uuid,
            'replaces_document_id' => $document->id,
            'version' => $document->version + 1,
        ]);
    }
}

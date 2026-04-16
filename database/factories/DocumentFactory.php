<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

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
            'distributor_id' => fn (array $attributes): int => Product::query()->findOrFail($attributes['product_id'])->distributor_id,
            'product_id' => Product::factory(),
            'type' => fake()->randomElement(DocumentType::cases()),
        ];
    }

    public function withFile(
        string $name = 'document.pdf',
        int $sizeKilobytes = 128,
        string $mimeType = 'application/pdf',
    ): static {
        return $this->withFiles([[
            'name' => $name,
            'sizeKilobytes' => $sizeKilobytes,
            'mimeType' => $mimeType,
        ]]);
    }

    /**
     * @param  array<int, array{name: string, sizeKilobytes?: int, mimeType?: string}>  $files
     */
    public function withFiles(array $files): static
    {
        return $this->afterCreating(function (Document $document) use ($files): void {
            foreach ($files as $file) {
                $document
                    ->addMedia(UploadedFile::fake()->create(
                        $file['name'],
                        $file['sizeKilobytes'] ?? 128,
                        $file['mimeType'] ?? 'application/pdf',
                    ))
                    ->toMediaCollection(Document::FILE_COLLECTION);
            }
        });
    }
}

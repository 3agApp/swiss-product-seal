<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $allTypes = array_map(
            static fn (DocumentType $type): string => $type->value,
            DocumentType::cases(),
        );

        shuffle($allTypes);

        $requiredDocumentTypes = array_slice($allTypes, 0, fake()->numberBetween(0, count($allTypes)));

        $allDataFields = [
            'safety_text',
            'warning_text',
            'age_grading',
            'material_information',
            'usage_restrictions',
            'safety_instructions',
            'additional_notes',
        ];

        shuffle($allDataFields);

        $requiredDataFields = array_slice($allDataFields, 0, fake()->numberBetween(0, count($allDataFields)));

        return [
            'distributor_id' => Distributor::factory(),
            'category_id' => fn (array $attributes): int => Category::factory()->create([
                'distributor_id' => $attributes['distributor_id'],
            ])->id,
            'name' => fake()->words(3, true),
            'required_document_types' => $requiredDocumentTypes,
            'required_data_fields' => $requiredDataFields,
        ];
    }
}

<?php

namespace App\Models;

use Database\Factories\ProductSafetyEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'product_id', 'safety_text', 'warning_text', 'age_grading', 'material_information', 'usage_restrictions', 'safety_instructions', 'additional_notes'])]
class ProductSafetyEntry extends Model
{
    /** @use HasFactory<ProductSafetyEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public static function dataFieldLabels(): array
    {
        return [
            'safety_text' => 'Safety text',
            'warning_text' => 'Warning text',
            'age_grading' => 'Age grading',
            'material_information' => 'Material information',
            'usage_restrictions' => 'Usage restrictions',
            'safety_instructions' => 'Safety instructions',
            'additional_notes' => 'Additional notes',
        ];
    }

    public static function isTemplateFieldRequiredForProduct(?Product $product, string $field): bool
    {
        return in_array($field, $product?->template?->required_data_fields ?? [], true);
    }

    public static function templateFieldHelperTextForProduct(?Product $product, string $field): ?string
    {
        if (! static::isTemplateFieldRequiredForProduct($product, $field)) {
            return null;
        }

        return 'Required by template';
    }

    protected static function booted(): void
    {
        static::creating(function (ProductSafetyEntry $productSafetyEntry): void {
            if (filled($productSafetyEntry->organization_id) || blank($productSafetyEntry->product_id)) {
                return;
            }

            $organizationId = Product::query()
                ->whereKey($productSafetyEntry->product_id)
                ->value('organization_id');

            if (filled($organizationId)) {
                $productSafetyEntry->organization_id = $organizationId;
            }
        });

        static::saved(function (ProductSafetyEntry $productSafetyEntry): void {
            $productSafetyEntry->product()->first()?->refreshCompletenessScore();
        });

        static::deleted(function (ProductSafetyEntry $productSafetyEntry): void {
            $productSafetyEntry->product()->first()?->refreshCompletenessScore();
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<int, string>
     */
    public function requiredTemplateFields(): array
    {
        $labels = static::dataFieldLabels();

        return collect($this->product?->template?->required_data_fields ?? [])
            ->filter(fn (string $field): bool => array_key_exists($field, $labels))
            ->values()
            ->all();
    }

    public function requiredTemplateFieldCount(): int
    {
        return count($this->requiredTemplateFields());
    }

    public function completedRequiredTemplateFieldCount(): int
    {
        return $this->requiredTemplateFieldCount() - count($this->missingRequiredTemplateFields());
    }

    /**
     * @return array<int, string>
     */
    public function requiredTemplateFieldLabels(): array
    {
        $labels = static::dataFieldLabels();

        return array_map(
            fn (string $field): string => $labels[$field],
            $this->requiredTemplateFields(),
        );
    }

    /**
     * @return array<int, string>
     */
    public function missingRequiredTemplateFields(): array
    {
        $labels = static::dataFieldLabels();

        return collect($this->requiredTemplateFields())
            ->reject(fn (string $field): bool => filled($this->getAttribute($field)))
            ->map(fn (string $field): string => $labels[$field])
            ->values()
            ->all();
    }

    public function templateCompletionStatus(): string
    {
        $requiredFieldCount = $this->requiredTemplateFieldCount();

        if ($requiredFieldCount === 0) {
            return 'Not required';
        }

        if ($this->completedRequiredTemplateFieldCount() === $requiredFieldCount) {
            return 'Complete';
        }

        return 'Incomplete';
    }

    public function templateCompletionSummary(): string
    {
        $requiredFieldCount = $this->requiredTemplateFieldCount();

        if ($requiredFieldCount === 0) {
            return 'The assigned template does not require any safety data fields.';
        }

        $completedFieldCount = $this->completedRequiredTemplateFieldCount();

        if ($completedFieldCount === $requiredFieldCount) {
            return "All {$requiredFieldCount} required safety fields are filled.";
        }

        return "{$completedFieldCount} of {$requiredFieldCount} required safety fields are filled.";
    }

    public function requiredTemplateFieldsSummary(): string
    {
        $requiredFields = $this->requiredTemplateFieldLabels();

        return filled($requiredFields)
            ? implode(', ', $requiredFields)
            : 'No safety fields required by the assigned template.';
    }

    public function missingRequiredTemplateFieldsSummary(): string
    {
        $missingFields = $this->missingRequiredTemplateFields();

        return filled($missingFields)
            ? implode(', ', $missingFields)
            : 'Nothing missing.';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'product_id' => 'integer',
        ];
    }
}

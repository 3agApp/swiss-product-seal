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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

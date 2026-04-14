<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\SealStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['organization_id', 'name', 'internal_article_number', 'supplier_article_number', 'order_number', 'ean', 'supplier_id', 'brand_id', 'category_id', 'template_id', 'status', 'completeness_score', 'seal_status_override', 'source_last_sync_at'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUuids;

    /**
     * @var array<string, string|int>
     */
    protected $attributes = [
        'status' => ProductStatus::Open->value,
        'completeness_score' => 0,
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function safetyEntry(): HasOne
    {
        return $this->hasOne(ProductSafetyEntry::class);
    }

    public function currentDocuments(): HasMany
    {
        return $this->documents()->where('is_current', true);
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['public_uuid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'supplier_id' => 'integer',
            'brand_id' => 'integer',
            'category_id' => 'integer',
            'template_id' => 'integer',
            'source_last_sync_at' => 'datetime',
            'status' => ProductStatus::class,
            'completeness_score' => 'decimal:2',
            'seal_status_override' => SealStatus::class,
        ];
    }

    public function sealStatus(): SealStatus
    {
        if ($this->seal_status_override !== null) {
            return $this->seal_status_override;
        }

        if ($this->status === ProductStatus::Approved) {
            return SealStatus::Verified;
        }

        if ($this->completeness_score > 0 || ! in_array($this->status, [ProductStatus::Open, null], true)) {
            return SealStatus::InProgress;
        }

        return SealStatus::NotVerified;
    }
}

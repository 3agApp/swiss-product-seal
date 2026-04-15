<?php

namespace App\Models;

use App\Enums\DocumentType;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['organization_id', 'product_id', 'type'])]
class Document extends Model implements HasMedia
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, InteractsWithMedia;

    public const FILE_COLLECTION = 'file';

    protected static function booted(): void
    {
        static::saved(function (Document $document): void {
            $document->product()->first()?->refreshCompletenessScore();
        });

        static::deleted(function (Document $document): void {
            $document->product()->first()?->refreshCompletenessScore();
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'product_id' => 'integer',
            'type' => DocumentType::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FILE_COLLECTION);
    }
}

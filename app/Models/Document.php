<?php

namespace App\Models;

use App\Enums\DocumentType;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'product_id',
    'type',
    'version_group_uuid',
    'replaces_document_id',
    'version',
    'expiry_date',
    'review_comment',
    'is_current',
    'public_download',
])]
class Document extends Model implements HasMedia
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, bool|int>
     */
    protected $attributes = [
        'version' => 1,
        'is_current' => true,
        'public_download' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $document): void {
            if (blank($document->version_group_uuid)) {
                $document->version_group_uuid = (string) Str::uuid();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function replacesDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_document_id');
    }

    public function replacementDocuments(): HasMany
    {
        return $this->hasMany(self::class, 'replaces_document_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile()
            ->useDisk('public');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'replaces_document_id' => 'integer',
            'version' => 'integer',
            'expiry_date' => 'date',
            'is_current' => 'boolean',
            'public_download' => 'boolean',
            'type' => DocumentType::class,
        ];
    }
}

<?php

namespace App\Models;

use App\Jobs\RecalculateProductCompleteness;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'category_id', 'name', 'required_document_types', 'required_data_fields'])]
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'required_document_types' => '[]',
        'required_data_fields' => '[]',
    ];

    protected static function booted(): void
    {
        static::saved(function (Template $template): void {
            RecalculateProductCompleteness::dispatch($template->getKey());
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'category_id' => 'integer',
            'required_document_types' => 'array',
            'required_data_fields' => 'array',
        ];
    }
}

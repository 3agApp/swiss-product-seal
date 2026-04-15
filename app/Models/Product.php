<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\ProductStatus;
use App\Enums\SealStatus;
use App\Notifications\ProductStatusChanged;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable(['organization_id', 'name', 'internal_article_number', 'supplier_article_number', 'order_number', 'ean', 'supplier_id', 'brand_id', 'category_id', 'template_id', 'status', 'clarification_note', 'completeness_score', 'last_reviewed_at', 'source_last_sync_at'])]
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

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->completeness_score = $product->calculateCompletenessScore();
        });
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('completeness_score', '>=', 100);
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('completeness_score', '<', 100);
    }

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

    public function safetyEntry(): HasOne
    {
        return $this->hasOne(ProductSafetyEntry::class);
    }

    public function safetyEntries(): HasMany
    {
        return $this->hasMany(ProductSafetyEntry::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
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
            'last_reviewed_at' => 'datetime',
            'status' => ProductStatus::class,
            'completeness_score' => 'decimal:2',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function requiredDocumentTypes(): array
    {
        $template = $this->currentTemplate();

        if (! $template instanceof Template) {
            return [];
        }

        return collect($template->required_document_types ?? [])
            ->map(fn (DocumentType|string $type): string => $type instanceof DocumentType ? $type->value : (string) $type)
            ->filter(fn (string $type): bool => DocumentType::tryFrom($type) instanceof DocumentType)
            ->unique()
            ->values()
            ->all();
    }

    public function requiredDocumentTypeCount(): int
    {
        return count($this->requiredDocumentTypes());
    }

    public function completedRequiredDocumentTypeCount(): int
    {
        return $this->requiredDocumentTypeCount() - count($this->missingRequiredDocumentTypes());
    }

    /**
     * @return array<int, string>
     */
    public function missingRequiredDocumentTypes(): array
    {
        $labels = DocumentType::options();
        $presentTypes = $this->presentDocumentTypes();

        return collect($this->requiredDocumentTypes())
            ->reject(fn (string $type): bool => in_array($type, $presentTypes, true))
            ->map(fn (string $type): string => $labels[$type] ?? $type)
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function requiredSafetyFields(): array
    {
        $labels = ProductSafetyEntry::dataFieldLabels();
        $template = $this->currentTemplate();

        if (! $template instanceof Template) {
            return [];
        }

        return collect($template->required_data_fields ?? [])
            ->filter(fn (string $field): bool => array_key_exists($field, $labels))
            ->unique()
            ->values()
            ->all();
    }

    public function requiredSafetyFieldCount(): int
    {
        return count($this->requiredSafetyFields());
    }

    public function completedRequiredSafetyFieldCount(): int
    {
        return $this->requiredSafetyFieldCount() - count($this->missingRequiredSafetyFields());
    }

    /**
     * @return array<int, string>
     */
    public function missingRequiredSafetyFields(): array
    {
        $labels = ProductSafetyEntry::dataFieldLabels();
        $safetyEntry = $this->currentSafetyEntry();

        return collect($this->requiredSafetyFields())
            ->reject(fn (string $field): bool => $safetyEntry instanceof ProductSafetyEntry && filled($safetyEntry->getAttribute($field)))
            ->map(fn (string $field): string => $labels[$field])
            ->values()
            ->all();
    }

    public function requiredComplianceItemCount(): int
    {
        return $this->requiredDocumentTypeCount() + $this->requiredSafetyFieldCount();
    }

    public function completedComplianceItemCount(): int
    {
        return $this->completedRequiredDocumentTypeCount() + $this->completedRequiredSafetyFieldCount();
    }

    public function calculateCompletenessScore(): float
    {
        $requiredItemCount = $this->requiredComplianceItemCount();

        if ($requiredItemCount === 0) {
            return 0.0;
        }

        return round(($this->completedComplianceItemCount() / $requiredItemCount) * 100, 2);
    }

    public function refreshCompletenessScore(): void
    {
        $score = $this->calculateCompletenessScore();

        if (! $this->exists) {
            $this->completeness_score = $score;

            return;
        }

        if (abs((float) $this->completeness_score - $score) < 0.01) {
            return;
        }

        $this->forceFill([
            'completeness_score' => $score,
        ])->saveQuietly();
    }

    public function canBeSubmittedForReview(): bool
    {
        $status = $this->status instanceof ProductStatus
            ? $this->status
            : ProductStatus::tryFrom((string) $this->status);

        if ($this->calculateCompletenessScore() < 100) {
            return false;
        }

        if (in_array($status, [ProductStatus::UnderReview, ProductStatus::Approved], true)) {
            return false;
        }

        if (in_array($status, [ProductStatus::Rejected, ProductStatus::ClarificationNeeded], true)
            && $this->last_reviewed_at
            && $this->updated_at <= $this->last_reviewed_at) {
            return false;
        }

        return true;
    }

    public function submitForReview(): bool
    {
        if (! $this->canBeSubmittedForReview()) {
            return false;
        }

        $this->clarification_note = null;

        return $this->transitionToStatus(ProductStatus::UnderReview, [
            ProductStatus::Open,
            ProductStatus::InProgress,
            ProductStatus::Rejected,
            ProductStatus::ClarificationNeeded,
        ]);
    }

    public function canBeApprovedByAdmin(): bool
    {
        return $this->status === ProductStatus::UnderReview;
    }

    public function approveByAdmin(): bool
    {
        if (! $this->canBeApprovedByAdmin()) {
            return false;
        }

        return $this->transitionToStatus(ProductStatus::Approved, [ProductStatus::UnderReview]);
    }

    public function canBeRejectedByAdmin(): bool
    {
        return $this->status === ProductStatus::UnderReview;
    }

    public function rejectByAdmin(): bool
    {
        if (! $this->canBeRejectedByAdmin()) {
            return false;
        }

        return $this->transitionToStatus(ProductStatus::Rejected, [ProductStatus::UnderReview]);
    }

    public function canHaveClarificationRequestedByAdmin(): bool
    {
        return $this->status === ProductStatus::UnderReview;
    }

    public function requestClarificationByAdmin(?string $note = null): bool
    {
        if (! $this->canHaveClarificationRequestedByAdmin()) {
            return false;
        }

        $this->clarification_note = $note;

        return $this->transitionToStatus(ProductStatus::ClarificationNeeded, [ProductStatus::UnderReview]);
    }

    public function completenessSummary(): string
    {
        $requiredItemCount = $this->requiredComplianceItemCount();

        if ($requiredItemCount === 0) {
            return 'No required documents or safety fields.';
        }

        return "{$this->completedComplianceItemCount()} of {$requiredItemCount} required items are present.";
    }

    public function missingRequirementsSummary(): string
    {
        $summary = [];

        if (filled($missingDocumentTypes = $this->missingRequiredDocumentTypes())) {
            $summary[] = 'Missing required documents: '.implode(', ', $missingDocumentTypes).'.';
        }

        if (filled($missingSafetyFields = $this->missingRequiredSafetyFields())) {
            $summary[] = 'Missing required safety fields: '.implode(', ', $missingSafetyFields).'.';
        }

        return filled($summary)
            ? implode(' ', $summary)
            : 'All required documents and safety fields are present.';
    }

    public function sealStatus(): SealStatus
    {
        if ($this->status === ProductStatus::Approved) {
            return SealStatus::Verified;
        }

        if ($this->completeness_score > 0 || ! in_array($this->status, [ProductStatus::Open, null], true)) {
            return SealStatus::InProgress;
        }

        return SealStatus::NotVerified;
    }

    public function publicUrl(): string
    {
        return route('products.public', $this->public_uuid);
    }

    public function qrCodeSvg(): string
    {
        $qrCode = new QRCode(new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgViewBoxSize' => 0,
        ]));

        return $qrCode->render($this->publicUrl());
    }

    private function currentTemplate(): ?Template
    {
        if (blank($this->template_id)) {
            return null;
        }

        $template = $this->getRelationValue('template');

        if ($template instanceof Template && (int) $template->getKey() === (int) $this->template_id) {
            return $template;
        }

        return Template::query()->find($this->template_id);
    }

    private function currentSafetyEntry(): ?ProductSafetyEntry
    {
        if ($this->relationLoaded('safetyEntry')) {
            return $this->safetyEntry;
        }

        if (! $this->exists) {
            return null;
        }

        return $this->safetyEntry()->first();
    }

    /**
     * @return array<int, string>
     */
    private function presentDocumentTypes(): array
    {
        return $this->currentDocuments()
            ->map(fn (Document $document): ?string => match (true) {
                $document->type instanceof DocumentType => $document->type->value,
                filled($document->type) => (string) $document->type,
                default => null,
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Document>
     */
    private function currentDocuments(): Collection
    {
        if ($this->relationLoaded('documents')) {
            return $this->documents;
        }

        if (! $this->exists) {
            return collect();
        }

        return $this->documents()->get();
    }

    /**
     * @param  array<int, ProductStatus>  $allowedFrom
     */
    private function transitionToStatus(ProductStatus $status, array $allowedFrom): bool
    {
        if (! in_array($this->status, $allowedFrom, true)) {
            return false;
        }

        $extraData = ['status' => $status];

        if (in_array($status, [ProductStatus::Approved, ProductStatus::Rejected, ProductStatus::ClarificationNeeded], true)) {
            $extraData['last_reviewed_at'] = now();
        }

        $this->forceFill($extraData)->save();

        $this->notifyStatusChange($status);

        return true;
    }

    private function notifyStatusChange(ProductStatus $status): void
    {
        $notification = new ProductStatusChanged($this, $status);

        if ($status === ProductStatus::UnderReview) {
            $admins = User::query()
                ->whereIn('email', config('admin.allowed_emails', []))
                ->get();

            $admins->each->notify($notification);
        } else {
            $this->organization
                ->members()
                ->get()
                ->each->notify($notification);
        }
    }
}

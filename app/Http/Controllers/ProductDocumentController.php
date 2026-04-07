<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Http\Requests\ProductDocumentStoreRequest;
use App\Models\Document;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductDocumentController extends Controller
{
    /**
     * Upload a document to a product.
     */
    public function store(ProductDocumentStoreRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();
        $documentType = DocumentType::from($validated['type']);
        $duplicateStrategy = $validated['duplicate_strategy'] ?? 'add_new';

        DB::transaction(function () use ($request, $product, $validated, $documentType, $duplicateStrategy): void {
            $documentToReplace = null;

            if ($duplicateStrategy === 'replace_existing') {
                $documentToReplace = $product->currentDocuments()
                    ->where('type', $documentType->value)
                    ->when(
                        isset($validated['replace_document_id']),
                        fn ($query) => $query->where('id', $validated['replace_document_id']),
                    )
                    ->firstOrFail();

                $documentToReplace->update(['is_current' => false]);
            }

            $document = $product->documents()->create([
                'type' => $documentType,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'review_comment' => $validated['review_comment'] ?? null,
                'version_group_uuid' => $documentToReplace?->version_group_uuid,
                'replaces_document_id' => $documentToReplace?->id,
                'version' => $documentToReplace
                    ? ((int) $product->documents()
                        ->where('version_group_uuid', $documentToReplace->version_group_uuid)
                        ->max('version')) + 1
                    : 1,
                'is_current' => true,
            ]);

            $document->addMedia($request->file('file'))
                ->toMediaCollection('file');
        });

        return response()->json([
            'documents' => $this->formatDocuments($product->fresh()),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatDocuments(Product $product): array
    {
        $documents = $product->documents()
            ->with('media')
            ->orderBy('type')
            ->orderByDesc('updated_at')
            ->get();

        $historyByGroup = $documents->groupBy('version_group_uuid');

        return $documents
            ->where('is_current', true)
            ->map(function (Document $document) use ($historyByGroup): array {
                $history = $historyByGroup
                    ->get($document->version_group_uuid)
                    ->reject(fn (Document $version): bool => $version->is($document))
                    ->sortByDesc('version')
                    ->map(fn (Document $version): array => $this->formatDocument($version))
                    ->values()
                    ->all();

                return [
                    ...$this->formatDocument($document),
                    'history' => $history,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDocument(Document $document): array
    {
        /** @var Media|null $media */
        $media = $document->getFirstMedia('file');

        return [
            'id' => $document->id,
            'type' => $document->type->value,
            'type_label' => $document->type->label(),
            'version' => $document->version,
            'expiry_date' => $document->expiry_date?->toDateString(),
            'review_comment' => $document->review_comment,
            'file_name' => $media?->file_name,
            'file_url' => $media?->getUrl(),
            'file_size' => $media?->size,
            'mime_type' => $media?->mime_type,
            'uploaded_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
            'replaces_document_id' => $document->replaces_document_id,
            'is_current' => $document->is_current,
        ];
    }
}

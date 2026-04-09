<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\ProductStatus;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Document;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $request = request();
        $search = $request->input('search', '');

        $allowedSorts = ['name', 'internal_article_number', 'ean', 'status'];
        $sort = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : null;
        $direction = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $products = Product::query()
            ->with(['supplier:id,name', 'brand:id,name', 'category:id,name', 'template:id,name', 'media'])
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('internal_article_number', 'like', "%{$search}%")
                    ->orWhere('supplier_article_number', 'like', "%{$search}%")
                    ->orWhere('ean', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%");
            }))
            ->when($sort, fn ($query) => $query->orderBy($sort, $direction), fn ($query) => $query->orderBy('id', 'desc'))
            ->paginate(15)
            ->withQueryString();

        $products->through(fn (Product $product) => array_merge($product->toArray(), [
            'image_url' => $product->getFirstMediaUrl('images') ?: null,
            'image_preview_url' => $product->getFirstMediaUrl('images', 'preview') ?: null,
        ]));

        return Inertia::render('products/index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
                'sort' => $sort ?? '',
                'direction' => $sort ? $direction : '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('products/create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name', 'supplier_id']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'templates' => Template::orderBy('name')->get(['id', 'name', 'category_id']),
            'statuses' => ProductStatus::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductStoreRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Product created successfully.',
        ]);

        return to_route('products.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): Response
    {
        $product->load(['supplier:id,name', 'brand:id,name,supplier_id', 'category:id,name', 'template:id,name,category_id', 'media']);

        return Inertia::render('products/edit', [
            'product' => array_merge($product->toArray(), [
                'image_url' => $product->getFirstMediaUrl('images') ?: null,
                'image_preview_url' => $product->getFirstMediaUrl('images', 'preview') ?: null,
                'images' => $product->getMedia('images')->map(fn (Media $media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'preview_url' => $media->getUrl('preview'),
                    'name' => $media->file_name,
                    'order' => $media->order_column,
                ])->values()->all(),
                'documents' => $this->formatDocuments($product),
            ]),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name', 'supplier_id']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'templates' => Template::orderBy('name')->get(['id', 'name', 'category_id']),
            'statuses' => ProductStatus::options(),
            'documentTypes' => DocumentType::options(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Product updated successfully.',
        ]);

        return to_route('products.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Product deleted successfully.',
        ]);

        return to_route('products.index');
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

<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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
            ->with(['supplier:id,name', 'brand:id,name', 'media'])
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
            'image_url' => $product->getFirstMediaUrl('image') ?: null,
            'image_preview_url' => $product->getFirstMediaUrl('image', 'preview') ?: null,
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
            'statuses' => ProductStatus::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $product = Product::create($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            $product->addMediaFromRequest('image')->toMediaCollection('image');
        }

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
        $product->load(['supplier:id,name', 'brand:id,name,supplier_id', 'media']);

        return Inertia::render('products/edit', [
            'product' => array_merge($product->toArray(), [
                'image_url' => $product->getFirstMediaUrl('image') ?: null,
                'image_preview_url' => $product->getFirstMediaUrl('image', 'preview') ?: null,
            ]),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name', 'supplier_id']),
            'statuses' => ProductStatus::options(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->safe()->except(['image', 'remove_image']));

        if ($request->hasFile('image')) {
            $product->addMediaFromRequest('image')->toMediaCollection('image');
        } elseif ($request->boolean('remove_image')) {
            $product->clearMediaCollection('image');
        }

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
}

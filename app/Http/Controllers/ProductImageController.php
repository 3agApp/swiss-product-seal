<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImageController extends Controller
{
    /**
     * Upload images to a product.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:10240'],
        ]);

        $currentCount = $product->getMedia('images')->count();
        $newCount = count($request->file('images'));

        if ($currentCount + $newCount > 10) {
            return response()->json([
                'message' => 'A product can have a maximum of 10 images.',
            ], 422);
        }

        foreach ($request->file('images') as $image) {
            $product->addMedia($image)->toMediaCollection('images');
        }

        return response()->json([
            'images' => $this->formatImages($product),
        ]);
    }

    /**
     * Remove an image from a product.
     */
    public function destroy(Product $product, Media $media): JsonResponse
    {
        if ($media->model_id !== $product->id || $media->model_type !== Product::class) {
            abort(404);
        }

        $media->delete();

        return response()->json([
            'images' => $this->formatImages($product),
        ]);
    }

    /**
     * Reorder product images.
     */
    public function reorder(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $requestedIds = collect($validated['ids'])
            ->map(static fn (int|string $id): int => (int) $id)
            ->values();

        $productImageIds = $product->media()
            ->where('collection_name', 'images')
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values();

        if (! $this->hasExactImageSet($requestedIds, $productImageIds)) {
            throw ValidationException::withMessages([
                'ids' => 'The provided image order must include each image for the selected product exactly once.',
            ]);
        }

        Media::setNewOrder($requestedIds->all());

        return response()->json([
            'images' => $this->formatImages($product),
        ]);
    }

    /**
     * @return array<int, array{id: int, url: string, preview_url: string, name: string, order: int}>
     */
    private function formatImages(Product $product): array
    {
        return $product->fresh()->getMedia('images')->map(fn (Media $media) => [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'preview_url' => $media->getUrl('preview'),
            'name' => $media->file_name,
            'order' => $media->order_column,
        ])->values()->all();
    }

    private function hasExactImageSet(Collection $requestedIds, Collection $productImageIds): bool
    {
        return $requestedIds->count() === $productImageIds->count()
            && $requestedIds->diff($productImageIds)->isEmpty()
            && $productImageIds->diff($requestedIds)->isEmpty();
    }
}

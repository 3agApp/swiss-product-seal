<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'stats' => Inertia::defer(fn () => [
                'totalProducts' => Product::count(),
                'totalSuppliers' => Supplier::count(),
                'totalCategories' => Category::count(),
                'statusCounts' => Product::query()
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'completenessDistribution' => [
                    'low' => Product::where('completeness_score', '<', 50)->count(),
                    'medium' => Product::whereBetween('completeness_score', [50, 79.99])->count(),
                    'high' => Product::where('completeness_score', '>=', 80)->count(),
                ],
            ]),
            'recentProducts' => Inertia::defer(fn () => Product::query()
                ->with(['supplier:id,name', 'category:id,name'])
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Product $product) => [
                    ...$product->only('id', 'name', 'status', 'completeness_score', 'updated_at'),
                    'supplier_name' => $product->supplier?->name,
                    'category_name' => $product->category?->name,
                    'image_preview_url' => $product->getFirstMediaUrl('images', 'preview') ?: null,
                ])
            ),
        ]);
    }
}

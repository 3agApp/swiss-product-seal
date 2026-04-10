<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $request = request();
        $search = $request->input('search', '');

        $allowedSorts = ['name', 'description'];
        $sort = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : null;
        $direction = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $categories = Category::query()
            ->withCount('products')
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($sort, fn ($query) => $query->orderBy($sort, $direction), fn ($query) => $query->orderBy('id', 'desc'))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('categories/index', [
            'categories' => $categories,
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
        return Inertia::render('categories/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Category created successfully.',
        ]);

        return to_route('categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): Response
    {
        return Inertia::render('categories/edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryUpdateRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Category updated successfully.',
        ]);

        return to_route('categories.edit', $category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Category cannot be deleted while products are assigned to it.',
            ]);

            return to_route('categories.index');
        }

        $category->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Category deleted successfully.',
        ]);

        return to_route('categories.index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Models\Category;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $request = request();
        $search = $request->input('search', '');

        $allowedSorts = ['name'];
        $sort = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : null;
        $direction = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $templates = Template::query()
            ->with('category:id,name')
            ->withCount('products')
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            }))
            ->when($sort, fn ($query) => $query->orderBy($sort, $direction), fn ($query) => $query->orderBy('id', 'desc'))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('templates/index', [
            'templates' => $templates,
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
        return Inertia::render('templates/create', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'documentTypes' => DocumentType::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        Template::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Template created successfully.',
        ]);

        return to_route('templates.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Template $template): Response
    {
        $template->load('category:id,name');

        return Inertia::render('templates/edit', [
            'template' => $template,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'documentTypes' => DocumentType::options(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        $template->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Template updated successfully.',
        ]);

        return to_route('templates.edit', $template);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Template $template): RedirectResponse
    {
        if ($template->products()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Template cannot be deleted while products are assigned to it.',
            ]);

            return to_route('templates.index');
        }

        $template->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Template deleted successfully.',
        ]);

        return to_route('templates.index');
    }
}

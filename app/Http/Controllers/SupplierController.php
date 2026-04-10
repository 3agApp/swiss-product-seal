<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierStoreRequest;
use App\Http\Requests\SupplierUpdateRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $request = request();
        $search = $request->input('search', '');

        $allowedSorts = ['supplier_code', 'name', 'country', 'email', 'active'];
        $sort = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : null;
        $direction = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $suppliers = Supplier::query()
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('supplier_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            }))
            ->when($sort, fn ($query) => $query->orderBy($sort, $direction), fn ($query) => $query->orderBy('id', 'desc'))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('suppliers/index', [
            'suppliers' => $suppliers,
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
        return Inertia::render('suppliers/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierStoreRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Supplier created successfully.',
        ]);

        return to_route('suppliers.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('suppliers/edit', [
            'supplier' => $supplier->load('brands'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierUpdateRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Supplier updated successfully.',
        ]);

        return to_route('suppliers.edit', $supplier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Supplier deleted successfully.',
        ]);

        return to_route('suppliers.index');
    }
}

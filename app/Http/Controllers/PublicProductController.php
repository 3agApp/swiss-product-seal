<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class PublicProductController extends Controller
{
    public function __invoke(string $publicUuid): View
    {
        $product = Product::query()
            ->where('public_uuid', $publicUuid)
            ->with(['organization', 'category', 'supplier', 'brand', 'template', 'safetyEntry', 'documents'])
            ->firstOrFail();

        return view('products.public', [
            'product' => $product,
            'sealStatus' => $product->sealStatus(),
        ]);
    }
}

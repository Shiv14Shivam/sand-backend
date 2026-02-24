<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * GET /api/products?category_id={id}&brand_id={id}
     * Returns products, filtered by category and/or brand.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id'    => ['nullable', 'integer', 'exists:brands,id'],
        ]);

        $products = Product::query()
            ->active()
            ->ordered()
            ->when($request->filled('category_id'), fn ($q) => $q->forCategory((int) $request->category_id))
            ->when($request->filled('brand_id'),    fn ($q) => $q->forBrand((int) $request->brand_id))
            ->with(['specifications'])
            ->get();

        return ProductResource::collection($products);
    }

    /**
     * GET /api/brands/{brand}/products
     * Returns all active products for a given brand (with specs auto-filled for the frontend).
     */
    public function byBrand(Brand $brand): AnonymousResourceCollection
    {
        $products = $brand->products()
            ->active()
            ->ordered()
            ->with(['specifications', 'category', 'brand'])
            ->get();

        return ProductResource::collection($products);
    }

    /**
     * GET /api/products/{product}
     * Returns a single product with all details (auto-fill on selection).
     */
    public function show(Product $product): ProductResource
    {
        $product->load(['specifications', 'category', 'brand']);

        return new ProductResource($product);
    }
}

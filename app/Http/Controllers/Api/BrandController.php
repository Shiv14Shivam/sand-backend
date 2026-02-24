<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    /**
     * GET /api/brands?category_id={id}
     * Returns brands, optionally filtered by category.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $brands = Brand::query()
            ->active()
            ->ordered()
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->forCategory((int) $request->category_id);
            })
            ->get();

        return BrandResource::collection($brands);
    }

    /**
     * GET /api/categories/{category}/brands
     * Returns all active brands for a given category.
     */
    public function byCategory(Category $category): AnonymousResourceCollection
    {
        $brands = $category->brands()->active()->ordered()->get();

        return BrandResource::collection($brands);
    }

    /**
     * GET /api/brands/{brand}
     */
    public function show(Brand $brand): BrandResource
    {
        return new BrandResource($brand->load('category'));
    }
}

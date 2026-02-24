<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * GET /api/categories
     * Returns all active categories.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::active()->ordered()->get();

        return CategoryResource::collection($categories);
    }

    /**
     * GET /api/categories/{category}
     * Returns a single category.
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }
}

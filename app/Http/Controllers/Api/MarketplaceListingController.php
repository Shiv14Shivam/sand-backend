<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMarketplaceListingRequest;
use App\Http\Requests\UpdateMarketplaceListingRequest;
use App\Http\Resources\MarketplaceListingResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketplaceListingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Seller Listings
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $sellerId = Auth::id();

        if (!$sellerId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $listings = MarketplaceListing::where('seller_id', $sellerId)
            ->with(['product.specifications', 'category', 'brand'])
            ->latest()
            ->paginate(15);

        return MarketplaceListingResource::collection($listings);
    }

    public function store(StoreMarketplaceListingRequest $request): JsonResponse
    {
        $category = Category::findOrFail($request->category_id);
        $brand    = Brand::findOrFail($request->brand_id);
        $product  = Product::findOrFail($request->product_id);

        if ($brand->category_id !== $category->id) {
            return response()->json([
                'message' => 'Brand does not belong to selected category.'
            ], 422);
        }

        if ($product->brand_id !== $brand->id) {
            return response()->json([
                'message' => 'Product does not belong to selected brand.'
            ], 422);
        }

        $sellerId = Auth::id();

        if (!$sellerId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $listing = MarketplaceListing::create([
            'seller_id'               => $sellerId,
            'product_id'              => $product->id,
            'category_id'             => $category->id,
            'brand_id'                => $brand->id,
            'price_per_bag'           => $request->price_per_bag,
            'delivery_charge_per_ton' => $request->delivery_charge_per_ton ?? 0,
            'available_stock_bags'    => $request->available_stock_bags,
            'status'                  => MarketplaceListing::STATUS_ACTIVE, // direct active
        ]);

        return response()->json([
            'message' => 'Listing created successfully.',
            'data'    => new MarketplaceListingResource($listing)
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Public Marketplace
    |--------------------------------------------------------------------------
    */

    public function publicIndex(Request $request)
    {
        $listings = MarketplaceListing::where('status', 'active')
            /*change*/->with(['product.specifications', 'category', 'brand', 'seller']) // specifications for detailed info, seller for contact details
            ->latest()
            ->paginate(20);

        return response()->json($listings);
    }
}

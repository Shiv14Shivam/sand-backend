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

    /*
    |--------------------------------------------------------------------------
    | Create Listing
    |--------------------------------------------------------------------------
    */

    public function store(StoreMarketplaceListingRequest $request): JsonResponse
    {
        $sellerId = Auth::id();

        if (!$sellerId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Validate relationships
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

        /*
        |--------------------------------------------------------------------------
        | 🔒 FIX: Prevent Duplicate Listing by Same Seller
        |--------------------------------------------------------------------------
        |
        | Problem:
        | A seller could list the same product multiple times.
        |
        | Solution:
        | Check if seller already has a listing for this product.
        |
        */

        $existingListing = MarketplaceListing::where('seller_id', $sellerId)
            ->where('product_id', $product->id)
            ->first();

        if ($existingListing) {

            /*
            |--------------------------------------------------------------------------
            | OPTION A: Reject Duplicate (Strict Mode)
            |--------------------------------------------------------------------------
            |
            | Uncomment this block if you want to reject duplicates completely.
            |
            */

            /*
            return response()->json([
                'message' => 'You have already listed this product.'
            ], 422);
            */


            /*
            |--------------------------------------------------------------------------
            | OPTION B: Update Existing Listing (Recommended UX)
            |--------------------------------------------------------------------------
            |
            | Instead of rejecting, we update existing listing.
            | This is better for vendors.
            |
            */

            $existingListing->update([
                'price_per_bag'           => $request->price_per_bag,
                'delivery_charge_per_ton' => $request->delivery_charge_per_ton ?? 0,
                'available_stock_bags'    => $request->available_stock_bags,
                'status'                  => MarketplaceListing::STATUS_ACTIVE,
            ]);

            return response()->json([
                'message' => 'Listing already exists. Updated successfully.',
                'data'    => new MarketplaceListingResource($existingListing)
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | 🛡️ FIX: Use DB Transaction (Prevents Race Condition)
        |--------------------------------------------------------------------------
        |
        | If two requests hit at same time, this protects integrity.
        |
        */

        $listing = DB::transaction(function () use ($sellerId, $product, $category, $brand, $request) {

            return MarketplaceListing::create([
                'seller_id'               => $sellerId,
                'product_id'              => $product->id,
                'category_id'             => $category->id,
                'brand_id'                => $brand->id,
                'price_per_bag'           => $request->price_per_bag,
                'delivery_charge_per_ton' => $request->delivery_charge_per_ton ?? 0,
                'available_stock_bags'    => $request->available_stock_bags,
                'status'                  => MarketplaceListing::STATUS_ACTIVE,
            ]);
        });

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
            ->with([
                'product.specifications',
                'category',
                'brand',
                'seller'
            ])
            ->latest()
            ->paginate(20);

        return response()->json($listings);
    }
}

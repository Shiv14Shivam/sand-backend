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
        */

        $existingListing = MarketplaceListing::where('seller_id', $sellerId)
            ->where('product_id', $product->id)
            ->first();

        if ($existingListing) {
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
                // ✅ FIX: load vendor's default address for warehouse coordinates
                'seller.addresses' => function ($q) {
                    $q->where('is_default', true)->limit(1);
                },
            ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $listings->getCollection()->map(function ($listing) {
                $seller        = $listing->seller;
                // ✅ vendor's default address — used for distance calculation
                $vendorDefault = $seller?->addresses->first();

                return [
                    'id'                      => $listing->id,
                    'price_per_bag'           => $listing->price_per_bag,
                    'delivery_charge_per_ton' => $listing->delivery_charge_per_ton,
                    'available_stock_bags'    => $listing->available_stock_bags,

                    'category' => [
                        'id'   => $listing->category?->id,
                        'name' => $listing->category?->name,
                    ],

                    'product' => [
                        'id'                   => $listing->product?->id,
                        'name'                 => $listing->product?->name,
                        'short_description'    => $listing->product?->short_description,
                        'detailed_description' => $listing->product?->detailed_description,
                        'image_url'            => $listing->product?->image_url,
                        'unit'                 => $listing->product?->unit,
                        'specifications'       => $listing->product?->specifications ?? [],
                    ],

                    'seller' => [
                        'id'    => $seller?->id,
                        'name'  => $seller?->name,
                        'phone' => $seller?->phone,
                        // ✅ coords from vendor's default address
                        // customer default ↔ vendor default = distance
                        'warehouse_lat' => $vendorDefault?->latitude,
                        'warehouse_lng' => $vendorDefault?->longitude,
                    ],
                ];
            }),
        ]);
    }
}

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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketplaceListingController extends Controller
{
    // ─── Seller's Own Listings ────────────────────────────────────────

    /**
     * GET /api/seller/listings
     * Returns all listings for the authenticated seller.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $sellerId = Auth::user()?->id;

        if (!$sellerId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $listings = MarketplaceListing::query()
            ->forSeller($sellerId)
            ->with(['product.specifications', 'category', 'brand'])
            ->latest()
            ->paginate(15);

        return MarketplaceListingResource::collection($listings);
    }

    /**
     * POST /api/seller/listings
     * Creates a new marketplace listing for the authenticated seller.
     */
    public function store(StoreMarketplaceListingRequest $request): JsonResponse
    {
        // Validate cross-field integrity: brand must belong to category, product must belong to brand
        $category = Category::findOrFail($request->category_id);
        $brand    = Brand::findOrFail($request->brand_id);
        $product  = Product::findOrFail($request->product_id);

        if ($brand->category_id !== $category->id) {
            return response()->json([
                'message' => 'The selected brand does not belong to the selected category.',
                'errors'  => ['brand_id' => ['Brand does not belong to the selected category.']],
            ], 422);
        }

        if ($product->brand_id !== $brand->id) {
            return response()->json([
                'message' => 'The selected product does not belong to the selected brand.',
                'errors'  => ['product_id' => ['Product does not belong to the selected brand.']],
            ], 422);
        }

        $sellerId = Auth::user()?->id;

        if (!$sellerId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Check if seller already has an active listing for this product
        $existing = MarketplaceListing::query()
            ->forSeller($sellerId)
            ->where('product_id', $product->id)
            ->whereIn('status', [MarketplaceListing::STATUS_ACTIVE, MarketplaceListing::STATUS_PENDING])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You already have an active or pending listing for this product.',
                'errors'  => ['product_id' => ['Duplicate listing not allowed.']],
            ], 422);
        }

        $listing = DB::transaction(function () use ($request, $product, $brand, $category, $sellerId) {
            return MarketplaceListing::create([
                'seller_id'               => $sellerId,
                'product_id'              => $product->id,
                'category_id'             => $category->id,
                'brand_id'                => $brand->id,
                'price_per_bag'           => $request->price_per_bag,
                'delivery_charge_per_ton' => $request->delivery_charge_per_ton ?? 0,
                'available_stock_bags'    => $request->available_stock_bags,
                'status'                  => MarketplaceListing::STATUS_PENDING,
            ]);
        });

        $listing->load(['product.specifications', 'category', 'brand']);

        return response()->json([
            'message' => 'Product listed in marketplace successfully.',
            'data'    => new MarketplaceListingResource($listing),
        ], 201);
    }

    /**
     * GET /api/seller/listings/{listing}
     * Returns a single listing for the authenticated seller.
     */
    public function show(MarketplaceListing $listing): JsonResponse
    {
        $this->authorizeOwnership($listing);

        $listing->load(['product.specifications', 'category', 'brand']);

        return response()->json([
            'data' => new MarketplaceListingResource($listing),
        ]);
    }

    /**
     * PUT /api/seller/listings/{listing}
     * Updates pricing / stock of a listing.
     */
    public function update(UpdateMarketplaceListingRequest $request, MarketplaceListing $listing): JsonResponse
    {
        $this->authorizeOwnership($listing);

        $listing->update($request->validated());
        $listing->load(['product.specifications', 'category', 'brand']);

        return response()->json([
            'message' => 'Listing updated successfully.',
            'data'    => new MarketplaceListingResource($listing),
        ]);
    }

    /**
     * DELETE /api/seller/listings/{listing}
     * Soft-deletes / removes a listing.
     */
    public function destroy(MarketplaceListing $listing): JsonResponse
    {
        $this->authorizeOwnership($listing);

        $listing->delete();

        return response()->json([
            'message' => 'Listing removed successfully.',
        ]);
    }

    // ─── Public Marketplace ───────────────────────────────────────────

    /**
     * GET /api/marketplace
     * Returns all active listings (public, for buyers).
     */
    public function publicIndex(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id'    => ['nullable', 'integer', 'exists:brands,id'],
            'product_id'  => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $listings = MarketplaceListing::query()
            ->active()
            ->with(['product.specifications', 'category', 'brand', 'seller'])
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('brand_id'),    fn($q) => $q->where('brand_id', $request->brand_id))
            ->when($request->filled('product_id'),  fn($q) => $q->where('product_id', $request->product_id))
            ->latest()
            ->paginate(20);

        return MarketplaceListingResource::collection($listings);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function authorizeOwnership(MarketplaceListing $listing): void
    {
        if ($listing->seller_id !== Auth::user()?->id) {
            abort(403, 'You do not have permission to access this listing.');
        }
    }
}

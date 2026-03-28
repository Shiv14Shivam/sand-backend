<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceListingResource;
use App\Models\MarketplaceListing;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorInventoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/inventory
    | Vendor's full inventory — all their listings with current stock
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:active,inactive,pending,rejected'],
        ]);

        $listings = MarketplaceListing::where('seller_id', Auth::id())
            ->with(['product.specifications', 'product.category', 'product.brand', 'category', 'brand'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('status')
            ->latest()
            ->paginate(20);

        // Enrich each listing with accepted order counts for the vendor dashboard
        $enriched = $listings->getCollection()->map(function (MarketplaceListing $listing) {
            $acceptedunit = OrderItem::where('listing_id', $listing->id)
                ->where('status', OrderItem::STATUS_ACCEPTED)
                ->sum('quantity_unit');

            $pendingunit = OrderItem::where('listing_id', $listing->id)
                ->where('status', OrderItem::STATUS_PENDING)
                ->sum('quantity_unit');

            return array_merge(
                (new MarketplaceListingResource($listing))->resolve(),
                [
                    'inventory_summary' => [
                        'available_stock_unit' => $listing->available_stock_unit,
                        'total_accepted_unit'  => (int) $acceptedunit,
                        'pending_request_unit' => (int) $pendingunit,
                        'is_low_stock'         => $listing->available_stock_unit <= 10,
                        'is_out_of_stock'      => $listing->available_stock_unit <= 0,
                    ],
                ]
            );
        });

        return response()->json([
            'data' => $enriched,
            'meta' => [
                'current_page' => $listings->currentPage(),
                'last_page'    => $listings->lastPage(),
                'per_page'     => $listings->perPage(),
                'total'        => $listings->total(),
            ],
            'stock_summary' => [
                'total_listings'    => MarketplaceListing::where('seller_id', Auth::id())->count(),
                'active_listings'   => MarketplaceListing::where('seller_id', Auth::id())->where('status', 'active')->count(),
                'out_of_stock'      => MarketplaceListing::where('seller_id', Auth::id())->where('available_stock_unit', 0)->count(),
                'low_stock'         => MarketplaceListing::where('seller_id', Auth::id())->where('available_stock_unit', '<=', 10)->where('available_stock_unit', '>', 0)->count(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/inventory/{id}
    | Single listing detail with full order history for that product
    |--------------------------------------------------------------------------
    */
    public function show(int $id): JsonResponse
    {
        $listing = MarketplaceListing::where('id', $id)
            ->where('seller_id', Auth::id())
            ->with(['product.specifications', 'product.category', 'product.brand', 'category', 'brand'])
            ->firstOrFail();

        // Order items for this listing — accepted and pending
        $orderItems = OrderItem::where('listing_id', $listing->id)
            ->with(['order.customer', 'order.deliveryAddress'])
            ->latest()
            ->get();

        $stats = [
            'available_stock_unit' => $listing->available_stock_unit,
            'total_orders'         => $orderItems->count(),
            'pending_orders'       => $orderItems->where('status', 'pending')->count(),
            'accepted_orders'      => $orderItems->where('status', 'accepted')->count(),
            'declined_orders'      => $orderItems->where('status', 'declined')->count(),
            'total_unit_sold'      => (int) $orderItems->whereIn('status', ['processing', 'delivered'])->where('payment_status', 'paid')->sum('quantity_unit'),
            'total_revenue'        => (float) $orderItems->where('payment_status', 'paid')->sum('subtotal'),
        ];

        return response()->json([
            'data'   => new MarketplaceListingResource($listing),
            'stats'  => $stats,
            'recent_orders' => $orderItems->take(10)->map(function (OrderItem $item) {
                return [
                    'order_item_id'  => $item->id,
                    'order_id'       => $item->order_id,
                    'customer_name'  => $item->order->customer->name,
                    'quantity_unit'  => $item->quantity_unit,
                    'subtotal'       => $item->subtotal,
                    'status'         => $item->status,
                    'placed_at'      => $item->order->created_at?->toISOString(),
                    'actioned_at'    => $item->actioned_at?->toISOString(),
                ];
            }),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PATCH /api/vendor/inventory/{id}/restock
    | Vendor manually adds stock to a listing
    |--------------------------------------------------------------------------
    */
    public function restock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'add_unit' => ['required', 'integer', 'min:1', 'max:999999'],
        ]);

        $listing = MarketplaceListing::where('id', $id)
            ->where('seller_id', Auth::id())
            ->firstOrFail();

        $listing->increment('available_stock_unit', $request->add_unit);

        // Auto-reactivate if it was set to inactive due to zero stock
        if ($listing->status === MarketplaceListing::STATUS_INACTIVE) {
            $listing->update(['status' => MarketplaceListing::STATUS_ACTIVE]);
        }

        return response()->json([
            'message'         => 'Stock updated successfully.',
            'new_stock_unit'  => $listing->fresh()->available_stock_unit,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PATCH /api/vendor/inventory/{id}/prices
    | Vendor updates price_per_unit and/or delivery_charge_per_km
    |--------------------------------------------------------------------------
    */
    public function updatePrices(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'price_per_unit'           => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:9999999'],
            'delivery_charge_per_km' => ['sometimes', 'required', 'numeric', 'min:0',    'max:9999999'],
        ]);

        $listing = MarketplaceListing::where('id', $id)
            ->where('seller_id', Auth::id())
            ->firstOrFail();

        $listing->update($request->only(['price_per_unit', 'delivery_charge_per_km']));

        return response()->json([
            'message'                  => 'Prices updated successfully.',
            'price_per_unit'            => $listing->fresh()->price_per_unit,
            'delivery_charge_per_km'  => $listing->fresh()->delivery_charge_per_km,
        ]);
    }
}

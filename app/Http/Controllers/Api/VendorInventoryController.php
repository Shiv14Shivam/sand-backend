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
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('status')
            ->latest()
            ->paginate(20);

        // Enrich each listing with accepted order counts for the vendor dashboard
        $enriched = $listings->getCollection()->map(function (MarketplaceListing $listing) {
            $acceptedBags = OrderItem::where('listing_id', $listing->id)
                ->where('status', OrderItem::STATUS_ACCEPTED)
                ->sum('quantity_bags');

            $pendingBags = OrderItem::where('listing_id', $listing->id)
                ->where('status', OrderItem::STATUS_PENDING)
                ->sum('quantity_bags');

            return array_merge(
                (new MarketplaceListingResource($listing))->resolve(),
                [
                    'inventory_summary' => [
                        'available_stock_bags' => $listing->available_stock_bags,
                        'total_accepted_bags'  => (int) $acceptedBags,
                        'pending_request_bags' => (int) $pendingBags,
                        'is_low_stock'         => $listing->available_stock_bags <= 10,
                        'is_out_of_stock'      => $listing->available_stock_bags <= 0,
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
                'out_of_stock'      => MarketplaceListing::where('seller_id', Auth::id())->where('available_stock_bags', 0)->count(),
                'low_stock'         => MarketplaceListing::where('seller_id', Auth::id())->where('available_stock_bags', '<=', 10)->where('available_stock_bags', '>', 0)->count(),
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
            'available_stock_bags' => $listing->available_stock_bags,
            'total_orders'         => $orderItems->count(),
            'pending_orders'       => $orderItems->where('status', 'pending')->count(),
            'accepted_orders'      => $orderItems->where('status', 'accepted')->count(),
            'declined_orders'      => $orderItems->where('status', 'declined')->count(),
            'total_bags_sold'      => (int) $orderItems->where('status', 'accepted')->sum('quantity_bags'),
            'total_revenue'        => (float) $orderItems->where('status', 'accepted')->sum('subtotal'),
        ];

        return response()->json([
            'data'   => new MarketplaceListingResource($listing),
            'stats'  => $stats,
            'recent_orders' => $orderItems->take(10)->map(function (OrderItem $item) {
                return [
                    'order_item_id'  => $item->id,
                    'order_id'       => $item->order_id,
                    'customer_name'  => $item->order->customer->name,
                    'quantity_bags'  => $item->quantity_bags,
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
            'add_bags' => ['required', 'integer', 'min:1', 'max:999999'],
        ]);

        $listing = MarketplaceListing::where('id', $id)
            ->where('seller_id', Auth::id())
            ->firstOrFail();

        $listing->increment('available_stock_bags', $request->add_bags);

        // Auto-reactivate if it was set to inactive due to zero stock
        if ($listing->status === MarketplaceListing::STATUS_INACTIVE) {
            $listing->update(['status' => MarketplaceListing::STATUS_ACTIVE]);
        }

        return response()->json([
            'message'         => 'Stock updated successfully.',
            'new_stock_bags'  => $listing->fresh()->available_stock_bags,
        ]);
    }
}

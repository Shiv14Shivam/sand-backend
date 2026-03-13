<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeclineOrderItemRequest;
use App\Http\Resources\OrderItemResource;
use App\Models\MarketplaceListing;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Events\OrderItemUpdated;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Models\User;
class VendorOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/orders
    | All order items sent to this vendor, filterable by status
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,accepted,declined'],
        ]);

        $items = OrderItem::where('vendor_id', Auth::id())
            ->with([
                'order.customer',
                'order.deliveryAddress',
                'product.specifications',
                'product.brand',
                'product.category',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        // Wrap each item with its parent order context for the vendor UI
        $data = $items->getCollection()->map(function (OrderItem $item) {
            return [
                'order_item'      => new OrderItemResource($item),
                'order_id'        => $item->order_id,
                'order_placed_at' => $item->order->created_at?->toISOString(),
                'customer'        => [
                    'id'    => $item->order->customer->id,
                    'name'  => $item->order->customer->name,
                    'phone' => $item->order->customer->phone,
                ],
                'delivery_address' => $item->order->deliveryAddress ? [
                    'label'          => $item->order->deliveryAddress->label,
                    'address_line_1' => $item->order->deliveryAddress->address_line_1,
                    'city'           => $item->order->deliveryAddress->city,
                    'state'          => $item->order->deliveryAddress->state,
                    'pincode'        => $item->order->deliveryAddress->pincode,
                ] : null,
            ];
        });

        return response()->json([
            'data'  => $data,
            'meta'  => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/orders/{id}
    | Single order item detail for the vendor
    |--------------------------------------------------------------------------
    */
    public function show(int $id): JsonResponse
    {
        $item = OrderItem::where('id', $id)
            ->where('vendor_id', Auth::id())
            ->with([
                'order.customer',
                'order.deliveryAddress',
                'product.specifications',
                'product.brand',
                'product.category',
                'listing',
            ])
            ->firstOrFail();

        return response()->json([
            'data' => new OrderItemResource($item),
            'order' => [
                'id'     => $item->order_id,
                'status' => $item->order->status,
                'notes'  => $item->order->notes,
            ],
            'customer' => [
                'id'    => $item->order->customer->id,
                'name'  => $item->order->customer->name,
                'phone' => $item->order->customer->phone,
            ],
            'delivery_address' => $item->order->deliveryAddress,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/vendor/orders/{id}/accept
    | Vendor accepts an order item → stock deducted from listing
    |--------------------------------------------------------------------------
    */
    public function accept(int $id): JsonResponse
    {
        $item = OrderItem::where('id', $id)
            ->where('vendor_id', Auth::id())
            ->firstOrFail();

        // ── Guard: only pending items can be acted on ──────────────────
        if ($item->status !== OrderItem::STATUS_PENDING) {
            return response()->json([
                'message' => 'This order item has already been ' . $item->status . '.',
            ], 422);
        }

        $listing = MarketplaceListing::findOrFail($item->listing_id);

        // ── Guard: re-check stock at time of acceptance ────────────────
        // (Handles race condition: stock may have depleted since order was placed)
        if ($listing->available_stock_bags < $item->quantity_bags) {
            return response()->json([
                'message'         => 'Cannot accept: insufficient stock. ' .
                    "Requested {$item->quantity_bags} bags, only {$listing->available_stock_bags} available.",
                'available_stock' => $listing->available_stock_bags,
            ], 422);
        }

        DB::transaction(function () use ($item, $listing) {

            // 1. Accept the order item
            $item->update([
                'status'      => OrderItem::STATUS_ACCEPTED,
                'actioned_at' => now(),
            ]);

            // 2. Deduct stock from the marketplace listing
            $listing->decrement('available_stock_bags', $item->quantity_bags);

            // 3. If stock hits 0, auto-set listing to inactive
            if ($listing->fresh()->available_stock_bags <= 0) {
                $listing->update(['status' => MarketplaceListing::STATUS_INACTIVE]);
            }

            // 4. Recalculate parent order status
            $item->order->recalculateStatus();
        });
        $customer = User::find($item->order->customer_id);
$customer->notify(new OrderStatusUpdatedNotification($item));
        broadcast(new OrderItemUpdated($item->fresh()->load('order')));

        return response()->json([
            'message'         => 'Order accepted. Stock updated.',
            'remaining_stock' => $listing->fresh()->available_stock_bags,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/vendor/orders/{id}/decline
    | Vendor declines an order item — stock NOT deducted
    |--------------------------------------------------------------------------
    */
    public function decline(DeclineOrderItemRequest $request, int $id): JsonResponse
    {
        $item = OrderItem::where('id', $id)
            ->where('vendor_id', Auth::id())
            ->firstOrFail();

        if ($item->status !== OrderItem::STATUS_PENDING) {
            return response()->json([
                'message' => 'This order item has already been ' . $item->status . '.',
            ], 422);
        }

        DB::transaction(function () use ($item, $request) {

            $item->update([
                'status'           => OrderItem::STATUS_DECLINED,
                'rejection_reason' => $request->rejection_reason,
                'actioned_at'      => now(),
            ]);

            // Recalculate parent order status
            $item->order->recalculateStatus();
        });
        $customer = User::find($item->order->customer_id);
$customer->notify(new OrderStatusUpdatedNotification($item));
        broadcast(new OrderItemUpdated($item->fresh()->load('order')));

        return response()->json([
            'message' => 'Order declined.',
        ]);
    }
}

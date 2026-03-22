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
    | Haversine distance formula
    | Returns distance in kilometers between two lat/lng coordinates
    |--------------------------------------------------------------------------
    */
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R    = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

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

        $vendor = Auth::user(); // current vendor — has warehouse_lat / warehouse_lng

        $items = OrderItem::where('vendor_id', $vendor->id)
            ->with([
                'order.customer',
                'order.deliveryAddress',
                'product.specifications',
                'product.brand',
                'product.category',
            ])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        $data = $items->getCollection()->map(function (OrderItem $item) use ($vendor) {

            $deliveryAddress = $item->order->deliveryAddress;

            // ── Resolve distance_km ──────────────────────────────────────────
            // Priority:
            //   1. Pre-computed value stored on the order item (set at placement)
            //   2. Calculate on-the-fly using Haversine if both coord sets exist
            //   3. null — Flutter will show "—"
            $distanceKm = $item->distance_km;

            if (($distanceKm === null || $distanceKm <= 0) && $deliveryAddress) {
                $vendorLat = (float) ($vendor->warehouse_lat ?? 0);
                $vendorLng = (float) ($vendor->warehouse_lng ?? 0);
                $custLat   = (float) ($deliveryAddress->latitude  ?? 0);
                $custLng   = (float) ($deliveryAddress->longitude ?? 0);

                if ($vendorLat && $vendorLng && $custLat && $custLng) {
                    $distanceKm = round(
                        $this->haversine($vendorLat, $vendorLng, $custLat, $custLng),
                        2
                    );

                    // Persist it so next fetch doesn't need to recalculate
                    $item->update(['distance_km' => $distanceKm]);
                }
            }

            // Build the OrderItemResource and inject the resolved distance
            $orderItemData                = (new OrderItemResource($item))->resolve();
            $orderItemData['distance_km'] = $distanceKm; // override with resolved value

            return [
                'order_item'      => $orderItemData,
                'order_id'        => $item->order_id,
                'order_placed_at' => $item->order->created_at?->toISOString(),
                'customer'        => [
                    'id'    => $item->order->customer->id,
                    'name'  => $item->order->customer->name,
                    'phone' => $item->order->customer->phone,
                ],
                'delivery_address' => $deliveryAddress ? [
                    'label'          => $deliveryAddress->label,
                    'address_line_1' => $deliveryAddress->address_line_1,
                    'city'           => $deliveryAddress->city,
                    'state'          => $deliveryAddress->state,
                    'pincode'        => $deliveryAddress->pincode,
                    'latitude'       => $deliveryAddress->latitude,
                    'longitude'      => $deliveryAddress->longitude,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
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
            'data'    => new OrderItemResource($item),
            'order'   => [
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

        if ($item->status !== OrderItem::STATUS_PENDING) {
            return response()->json([
                'message' => 'This order item has already been ' . $item->status . '.',
            ], 422);
        }

        $listing = MarketplaceListing::findOrFail($item->listing_id);

        if ($listing->available_stock_unit < $item->quantity_unit) {
            return response()->json([
                'message'         => 'Cannot accept: insufficient stock. ' .
                    "Requested {$item->quantity_unit} unit, only {$listing->available_stock_unit} available.",
                'available_stock' => $listing->available_stock_unit,
            ], 422);
        }

        DB::transaction(function () use ($item, $listing) {

            $item->update([
                'status'      => OrderItem::STATUS_ACCEPTED,
                'actioned_at' => now(),
            ]);

            $listing->decrement('available_stock_unit', $item->quantity_unit);

            if ($listing->fresh()->available_stock_unit <= 0) {
                $listing->update(['status' => MarketplaceListing::STATUS_INACTIVE]);
            }

            $item->order->recalculateStatus();
        });
        $customer = User::find($item->order->customer_id);
        $customer->notify(new OrderStatusUpdatedNotification($item));
        broadcast(new OrderItemUpdated($item->fresh()->load('order')));

        return response()->json([
            'message'         => 'Order accepted. Stock updated.',
            'remaining_stock' => $listing->fresh()->available_stock_unit,
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

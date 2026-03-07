<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceDirectOrderRequest;
use App\Http\Requests\PlaceCartOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\MarketplaceListing;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | POST /api/orders/direct
    | Customer places an order for a single listing directly (no cart)
    |--------------------------------------------------------------------------
    */
    public function placeDirect(PlaceDirectOrderRequest $request): JsonResponse
    {
        $customerId = Auth::id();

        $listing = MarketplaceListing::findOrFail($request->listing_id);

        // ── Guards ─────────────────────────────────────────────────────
        if ($listing->status !== MarketplaceListing::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'This listing is not currently available.',
            ], 422);
        }

        if ($listing->seller_id === $customerId) {
            return response()->json([
                'message' => 'You cannot order from your own listing.',
            ], 422);
        }

        if ($listing->available_stock_bags < $request->quantity_bags) {
            return response()->json([
                'message'         => 'Insufficient stock. Only ' . $listing->available_stock_bags . ' bags available.',
                'available_stock' => $listing->available_stock_bags,
            ], 422);
        }

        // ── Validate delivery address ownership ────────────────────────
        if ($request->delivery_address_id) {
            $ownsAddress = \App\Models\Address::where('id', $request->delivery_address_id)
                ->where('user_id', $customerId)
                ->exists();

            if (!$ownsAddress) {
                return response()->json(['message' => 'Invalid delivery address.'], 422);
            }
        }

        $subtotal = round($listing->price_per_bag * $request->quantity_bags, 2);

        $order = DB::transaction(function () use ($customerId, $listing, $request, $subtotal) {

            $order = Order::create([
                'customer_id'         => $customerId,
                'delivery_address_id' => $request->delivery_address_id,
                'status'              => Order::STATUS_PENDING,
                'total_amount'        => $subtotal,
                'notes'               => $request->notes,
            ]);

            OrderItem::create([
                'order_id'                => $order->id,
                'listing_id'              => $listing->id,
                'vendor_id'               => $listing->seller_id,
                'product_id'              => $listing->product_id,
                'quantity_bags'           => $request->quantity_bags,
                'price_per_bag'           => $listing->price_per_bag,
                'delivery_charge_per_ton' => $listing->delivery_charge_per_ton,
                'subtotal'                => $subtotal,
                'status'                  => OrderItem::STATUS_PENDING,
            ]);

            return $order;
        });

        $order->load(['items.product.specifications', 'items.vendor', 'deliveryAddress']);

        return response()->json([
            'message' => 'Order placed successfully. Awaiting vendor confirmation.',
            'data'    => new OrderResource($order),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/orders/from-cart
    | Customer checks out their entire cart — one order, multiple vendors
    |--------------------------------------------------------------------------
    */
    public function placeFromCart(PlaceCartOrderRequest $request): JsonResponse
    {
        $customerId = Auth::id();

        $cartItems = Cart::where('user_id', $customerId)
            ->with('listing')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        // ── Validate delivery address ownership ────────────────────────
        if ($request->delivery_address_id) {
            $ownsAddress = \App\Models\Address::where('id', $request->delivery_address_id)
                ->where('user_id', $customerId)
                ->exists();

            if (!$ownsAddress) {
                return response()->json(['message' => 'Invalid delivery address.'], 422);
            }
        }

        // ── Pre-flight stock validation for ALL items ──────────────────
        // We do this before touching the DB so the whole checkout fails
        // cleanly if any single item has an issue.
        $errors = [];

        foreach ($cartItems as $item) {
            $listing = $item->listing;

            if (!$listing) {
                $errors[] = "A listing in your cart (cart item #{$item->id}) no longer exists. Please remove it.";
                continue;
            }

            if ($listing->status !== MarketplaceListing::STATUS_ACTIVE) {
                $errors[] = "Listing for cart item #{$item->id} is no longer active. Please remove it.";
                continue;
            }

            if ($listing->seller_id === $customerId) {
                $errors[] = "You cannot order your own listing (cart item #{$item->id}).";
                continue;
            }

            if ($listing->available_stock_bags < $item->quantity_bags) {
                $errors[] = "Insufficient stock for cart item #{$item->id}. " .
                    "Requested: {$item->quantity_bags} bags, Available: {$listing->available_stock_bags} bags.";
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some items in your cart have issues. Please review.',
                'errors'  => $errors,
            ], 422);
        }

        // ── Create order inside transaction ────────────────────────────
        $order = DB::transaction(function () use ($customerId, $cartItems, $request) {

            $totalAmount = 0;

            // Calculate total
            foreach ($cartItems as $item) {
                $totalAmount += round($item->listing->price_per_bag * $item->quantity_bags, 2);
            }

            $order = Order::create([
                'customer_id'         => $customerId,
                'delivery_address_id' => $request->delivery_address_id,
                'status'              => Order::STATUS_PENDING,
                'total_amount'        => $totalAmount,
                'notes'               => $request->notes,
            ]);

            foreach ($cartItems as $item) {
                $listing = $item->listing;

                OrderItem::create([
                    'order_id'                => $order->id,
                    'listing_id'              => $listing->id,
                    'vendor_id'               => $listing->seller_id,
                    'product_id'              => $listing->product_id,
                    'quantity_bags'           => $item->quantity_bags,
                    'price_per_bag'           => $listing->price_per_bag,
                    'delivery_charge_per_ton' => $listing->delivery_charge_per_ton,
                    'subtotal'                => round($listing->price_per_bag * $item->quantity_bags, 2),
                    'status'                  => OrderItem::STATUS_PENDING,
                ]);
            }

            // Clear the cart after successful order
            Cart::where('user_id', $customerId)->delete();

            return $order;
        });

        $order->load(['items.product.specifications', 'items.vendor', 'deliveryAddress']);

        return response()->json([
            'message' => 'Order placed successfully. Awaiting vendor confirmations.',
            'data'    => new OrderResource($order),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/orders
    | Customer's full order history with pagination
    |--------------------------------------------------------------------------
    */
    public function history(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', Auth::id())
            ->with(['items.product', 'items.vendor', 'deliveryAddress'])
            ->latest()
            ->paginate(15);

        return response()->json(OrderResource::collection($orders)->response()->getData(true));
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/orders/{id}
    | Single order detail for the customer
    |--------------------------------------------------------------------------
    */
    public function show(int $id): JsonResponse
    {
        $order = Order::where('id', $id)
            ->where('customer_id', Auth::id())
            ->with(['items.product.specifications', 'items.vendor', 'deliveryAddress'])
            ->firstOrFail();

        return response()->json(['data' => new OrderResource($order)]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/orders/{id}/cancel
    | Customer cancels an order — only allowed while fully pending
    |--------------------------------------------------------------------------
    */
    public function cancel(int $id): JsonResponse
    {
        $order = Order::where('id', $id)
            ->where('customer_id', Auth::id())
            ->firstOrFail();

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'message' => 'Only fully pending orders can be cancelled. ' .
                    'Please contact vendors for partial orders.',
            ], 422);
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return response()->json(['message' => 'Order cancelled successfully.']);
    }
}

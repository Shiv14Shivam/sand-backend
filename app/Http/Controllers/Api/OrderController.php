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
use App\Events\OrderPlaced; // Added for WebSocket broadcasting
use App\Notifications\OrderPlacedNotification;
use App\Models\User;

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

        if ($listing->available_stock_unit < $request->quantity_unit) {
            return response()->json([
                'message'         => 'Insufficient stock. Only ' . $listing->available_stock_unit . ' unit available.',
                'available_stock' => $listing->available_stock_unit,
            ], 422);
        }

        if ($request->delivery_address_id) {
            $ownsAddress = \App\Models\Address::where('id', $request->delivery_address_id)
                ->where('user_id', $customerId)
                ->exists();

            if (!$ownsAddress) {
                return response()->json(['message' => 'Invalid delivery address.'], 422);
            }
        }

        $subtotal = round($listing->price_per_unit * $request->quantity_unit, 2);

        $order = DB::transaction(function () use ($customerId, $listing, $request, $subtotal) {

            $order = Order::create([
                'customer_id'         => $customerId,
                'delivery_address_id' => $request->delivery_address_id,
                'status'              => Order::STATUS_PENDING,
                'total_amount'        => $subtotal,
                'notes'               => $request->notes,
            ]);

            $item = OrderItem::create([
                'order_id'                => $order->id,
                'listing_id'              => $listing->id,
                'vendor_id'               => $listing->seller_id,
                'product_id'              => $listing->product_id,
                'quantity_unit'           => $request->quantity_unit,
                'price_per_unit'           => $listing->price_per_unit,
                'delivery_charge_per_km' => $listing->delivery_charge_per_km,
                'subtotal'                => $subtotal,
                'status'                  => OrderItem::STATUS_PENDING,
            ]);

            // Broadcast event to vendor
            $vendor = User::find($item->vendor_id);
            $vendor->notify(new OrderPlacedNotification($item));

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

        if ($request->delivery_address_id) {
            $ownsAddress = \App\Models\Address::where('id', $request->delivery_address_id)
                ->where('user_id', $customerId)
                ->exists();

            if (!$ownsAddress) {
                return response()->json(['message' => 'Invalid delivery address.'], 422);
            }
        }

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

            if ($listing->available_stock_unit < $item->quantity_unit) {
                $errors[] = "Insufficient stock for cart item #{$item->id}. Requested: {$item->quantity_unit} unit, Available: {$listing->available_stock_unit} unit.";
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some items in your cart have issues. Please review.',
                'errors'  => $errors,
            ], 422);
        }

        $order = DB::transaction(function () use ($customerId, $cartItems, $request) {

            $totalAmount = 0;

            foreach ($cartItems as $item) {
                $totalAmount += round($item->listing->price_per_unit * $item->quantity_unit, 2);
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

                $orderItem = OrderItem::create([
                    'order_id'                => $order->id,
                    'listing_id'              => $listing->id,
                    'vendor_id'               => $listing->seller_id,
                    'product_id'              => $listing->product_id,
                    'quantity_unit'           => $item->quantity_unit,
                    'price_per_unit'           => $listing->price_per_unit,
                    'delivery_charge_per_km' => $listing->delivery_charge_per_km,
                    'subtotal'                => round($listing->price_per_unit * $item->quantity_unit, 2),
                    'status'                  => OrderItem::STATUS_PENDING,
                ]);

                // Broadcast event to each vendor
                broadcast(new OrderPlaced($orderItem));
            }

            Cart::where('user_id', $customerId)->delete();

            return $order;
        });

        $order->load(['items.product.specifications', 'items.vendor', 'deliveryAddress']);

        return response()->json([
            'message' => 'Order placed successfully. Awaiting vendor confirmations.',
            'data'    => new OrderResource($order),
        ], 201);
    }
}

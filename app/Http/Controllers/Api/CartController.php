<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use App\Models\MarketplaceListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/cart
    | View the authenticated customer's cart
    |--------------------------------------------------------------------------
    */
    public function index(): JsonResponse
    {
        $items = Cart::where('user_id', Auth::id())
            ->with([
                'listing.product.specifications',
                'listing.brand',
                'listing.category',
                'listing.seller',
            ])
            ->get();

        $subtotal = $items->sum(function ($item) {
            return $item->listing
                ? round($item->listing->price_per_bag * $item->quantity_bags, 2)
                : 0;
        });

        return response()->json([
            'data'     => CartItemResource::collection($items),
            'summary'  => [
                'total_items'  => $items->count(),
                'subtotal'     => $subtotal,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/cart
    | Add a listing to the cart (or update quantity if already present)
    |--------------------------------------------------------------------------
    */
    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $listing = MarketplaceListing::findOrFail($request->listing_id);

        // ── Guard: listing must be active ──────────────────────────────
        if ($listing->status !== MarketplaceListing::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'This product is not available for ordering right now.',
            ], 422);
        }

        // ── Guard: sufficient stock ────────────────────────────────────
        if ($listing->available_stock_bags < $request->quantity_bags) {
            return response()->json([
                'message'         => 'Insufficient stock.',
                'available_stock' => $listing->available_stock_bags,
            ], 422);
        }

        // ── Guard: customer cannot buy from their own listing ──────────
        if ($listing->seller_id === Auth::id()) {
            return response()->json([
                'message' => 'You cannot add your own listing to the cart.',
            ], 422);
        }

        $cartItem = Cart::updateOrCreate(
            [
                'user_id'    => Auth::id(),
                'listing_id' => $request->listing_id,
            ],
            [
                'quantity_bags' => $request->quantity_bags,
            ]
        );

        $cartItem->load([
            'listing.product.specifications',
            'listing.brand',
            'listing.category',
            'listing.seller',
        ]);

        return response()->json([
            'message' => $cartItem->wasRecentlyCreated ? 'Item added to cart.' : 'Cart item updated.',
            'data'    => new CartItemResource($cartItem),
        ], $cartItem->wasRecentlyCreated ? 201 : 200);
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/cart/{id}
    | Update quantity of a cart item
    |--------------------------------------------------------------------------
    */
    public function update(StoreCartItemRequest $request, int $id): JsonResponse
    {
        $cartItem = Cart::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $listing = $cartItem->listing;

        // ── Guard: listing still active ────────────────────────────────
        if ($listing->status !== MarketplaceListing::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'This listing is no longer active. Please remove it from your cart.',
            ], 422);
        }

        // ── Guard: sufficient stock ────────────────────────────────────
        if ($listing->available_stock_bags < $request->quantity_bags) {
            return response()->json([
                'message'         => 'Insufficient stock.',
                'available_stock' => $listing->available_stock_bags,
            ], 422);
        }

        $cartItem->update(['quantity_bags' => $request->quantity_bags]);

        return response()->json([
            'message' => 'Cart updated.',
            'data'    => new CartItemResource($cartItem->load('listing.product', 'listing.brand', 'listing.category', 'listing.seller')),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/cart/{id}
    | Remove a single item from the cart
    |--------------------------------------------------------------------------
    */
    public function destroy(int $id): JsonResponse
    {
        Cart::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['message' => 'Item removed from cart.']);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/cart
    | Clear the entire cart
    |--------------------------------------------------------------------------
    */
    public function clear(): JsonResponse
    {
        Cart::where('user_id', Auth::id())->delete();

        return response()->json(['message' => 'Cart cleared.']);
    }
}

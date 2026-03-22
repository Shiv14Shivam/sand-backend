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
    | SHARED EAGER LOAD RELATIONS
    |--------------------------------------------------------------------------
    | Defined once, used in index(), store(), and update() to stay consistent.
    |
    | Why seller.addresses?
    | CartItemResource exposes seller.warehouse_lat and seller.warehouse_lng
    | which are taken from the vendor's default address (is_default = true).
    | Flutter's CartPage uses these coordinates in the Haversine formula to
    | auto-calculate the delivery distance for each cart item.
    |
    | Without this, warehouse_lat/lng will be null and Flutter falls back
    | to a hardcoded 5.0 km estimate instead of the real distance.
    |--------------------------------------------------------------------------
    */
    private function cartRelations(): array
    {
        return [
            'listing.product.specifications',
            'listing.brand',
            'listing.category',
            'listing.seller.addresses' => function ($q) {
                // Only load vendor's default address (warehouse location)
                // lat/lng from this address is used by Flutter for distance calc
                $q->where('is_default', true)->limit(1);
            },
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/cart
    |--------------------------------------------------------------------------
    | Returns all cart items for the authenticated customer.
    |
    | Response includes:
    |   - data: array of CartItemResource (product, brand, category, seller)
    |   - summary: total_items count + subtotal (price x qty, no delivery)
    |
    | Delivery cost is NOT included here — Flutter calculates it client-side
    | using the Haversine formula with seller warehouse coordinates.
    |
    | Called by: Flutter CartPage on load + pull to refresh
    |            Flutter CustomerHomePage _loadCartCount() on init
    |--------------------------------------------------------------------------
    */
    public function index(): JsonResponse
    {
        $items = Cart::where('user_id', Auth::id())
            ->with($this->cartRelations())
            ->get();

        // Subtotal = sum of (price_per_unit x quantity_unit) for all items
        // Does not include delivery charges (calculated client-side)
        $subtotal = $items->sum(function ($item) {
            return $item->listing
                ? round($item->listing->price_per_unit * $item->quantity_unit, 2)
                : 0;
        });

        return response()->json([
            'data'    => CartItemResource::collection($items),
            'summary' => [
                'total_items' => $items->count(),
                'subtotal'    => $subtotal,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/cart
    |--------------------------------------------------------------------------
    | Adds a listing to the cart, or updates quantity if already present.
    | Uses updateOrCreate so the same listing is never duplicated.
    |
    | Guards (in order):
    |   1. Listing must be active (STATUS_ACTIVE)
    |   2. Requested quantity must not exceed available stock
    |   3. Customer cannot add their own listing to cart
    |
    | Returns 201 if newly created, 200 if quantity was updated.
    |
    | Called by: Flutter CustomerHomePage _addToCart()
    |            User enters quantity then taps "Add to Cart" in product modal
    |            This quantity_unit value is what CartPage reads back later
    |--------------------------------------------------------------------------
    */
    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $listing = MarketplaceListing::findOrFail($request->listing_id);

        // Guard 1: Listing must be active
        if ($listing->status !== MarketplaceListing::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'This product is not available for ordering right now.',
            ], 422);
        }

        // Guard 2: Requested quantity must not exceed available stock
        if ($listing->available_stock_unit < $request->quantity_unit) {
            return response()->json([
                'message'         => 'Insufficient stock.',
                'available_stock' => $listing->available_stock_unit,
            ], 422);
        }

        // Guard 3: Vendor cannot buy from their own listing
        if ($listing->seller_id === Auth::id()) {
            return response()->json([
                'message' => 'You cannot add your own listing to the cart.',
            ], 422);
        }

        // Upsert: create new or update quantity if same listing already in cart
        $cartItem = Cart::updateOrCreate(
            [
                'user_id'    => Auth::id(),
                'listing_id' => $request->listing_id,
            ],
            [
                'quantity_unit' => $request->quantity_unit,
            ]
        );

        // Load all relations including seller.addresses for warehouse coordinates
        $cartItem->load($this->cartRelations());

        return response()->json([
            'message' => $cartItem->wasRecentlyCreated
                ? 'Item added to cart.'
                : 'Cart item updated.',
            'data' => new CartItemResource($cartItem),
        ], $cartItem->wasRecentlyCreated ? 201 : 200);
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/cart/{id}
    |--------------------------------------------------------------------------
    | Updates the quantity of an existing cart item.
    | Scoped to the authenticated user — cannot update another user's cart.
    |
    | Guards:
    |   1. Listing must still be active (could be paused since item was added)
    |   2. New quantity must not exceed current available stock
    |
    | Called by: Flutter CartPage when user taps + or - on a cart item
    |            Optimistic UI — Flutter updates immediately, rolls back on fail
    |--------------------------------------------------------------------------
    */
    public function update(StoreCartItemRequest $request, int $id): JsonResponse
    {
        // Scoped to authenticated user — prevents updating other users' carts
        $cartItem = Cart::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $listing = $cartItem->listing;

        // Guard 1: Listing must still be active
        // Vendor may have paused listing after customer added it to cart
        if ($listing->status !== MarketplaceListing::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'This listing is no longer active. Please remove it from your cart.',
            ], 422);
        }

        // Guard 2: New quantity must not exceed current available stock
        if ($listing->available_stock_unit < $request->quantity_unit) {
            return response()->json([
                'message'         => 'Insufficient stock.',
                'available_stock' => $listing->available_stock_unit,
            ], 422);
        }

        $cartItem->update(['quantity_unit' => $request->quantity_unit]);

        // Reload with full relations including seller.addresses for coordinates
        $cartItem->load($this->cartRelations());

        return response()->json([
            'message' => 'Cart updated.',
            'data'    => new CartItemResource($cartItem),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/cart/{id}
    |--------------------------------------------------------------------------
    | Removes a single item from the authenticated customer's cart.
    | Scoped to user_id — cannot delete another user's cart items.
    | Returns 200 even if item did not exist (safe idempotent behavior).
    |
    | Called by: Flutter CartPage when user taps delete on a cart item
    |            Optimistic UI — Flutter removes immediately, rolls back on fail
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
    | DELETE /api/cart/clear
    |--------------------------------------------------------------------------
    | Removes ALL items from the authenticated customer's cart at once.
    | Hard delete — cannot be undone.
    |
    | Called by: Flutter CartPage when user confirms "Clear All" in the dialog
    |--------------------------------------------------------------------------
    */
    public function clear(): JsonResponse
    {
        Cart::where('user_id', Auth::id())->delete();

        return response()->json(['message' => 'Cart cleared.']);
    }
}

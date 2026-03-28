<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\MarketplaceListingController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\VendorInventoryController;
use App\Http\Controllers\Api\VendorOrderController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| Public — Categories / Brands / Products / Marketplace
|--------------------------------------------------------------------------
*/

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::get('/brands', [BrandController::class, 'index']);
Route::get('/categories/{category}/brands', [BrandController::class, 'byCategory']);
Route::get('/brands/{brand}', [BrandController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/brands/{brand}/products', [ProductController::class, 'byBrand']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::get('/marketplace', [MarketplaceListingController::class, 'publicIndex']);

/*
|--------------------------------------------------------------------------
| Public — Auth
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Protected — Authenticated Users
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ─────────────────────────────────────────
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/email/resend', [AuthController::class, 'resendVerification']);

    // ── Addresses ────────────────────────────────────
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
    Route::post('/addresses/{id}/default', [AddressController::class, 'setDefault']);
    Route::get('/address/default', [AddressController::class, 'getDefault']);

    // ── Seller Listings ──────────────────────────────
    Route::get('/seller/listings', [MarketplaceListingController::class, 'index']);
    Route::post('/seller/listings', [MarketplaceListingController::class, 'store']);
    Route::get('/seller/listings/{id}', [MarketplaceListingController::class, 'show']);
    Route::put('/seller/listings/{id}', [MarketplaceListingController::class, 'update']);
    Route::delete('/seller/listings/{id}', [MarketplaceListingController::class, 'destroy']);

    // ── Notifications ────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    // ── Cart ─────────────────────────────────────────
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{id}', [CartController::class, 'update']);
        Route::delete('/clear', [CartController::class, 'clear']);
        Route::delete('/{id}', [CartController::class, 'destroy']);
    });

    // ── Orders + Payments ────────────────────────────
    Route::prefix('orders')->group(function () {

        // Orders
        Route::get('/', [OrderController::class, 'history']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/direct', [OrderController::class, 'placeDirect']);
        Route::post('/from-cart', [OrderController::class, 'placeFromCart']);
        Route::delete('/{id}/cancel', [OrderController::class, 'cancel']);

        // Payments
        Route::post('/{orderItemId}/pay-now', [PaymentController::class, 'payNow']);
        Route::post('/{orderItemId}/pay-later', [PaymentController::class, 'payLater']);
        Route::get('/{orderItemId}/payment-status', [PaymentController::class, 'status']);
    });

    // ── Role Dashboards ──────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', fn() => response()->json(['message' => 'Admin only']));
    });

    Route::middleware('role:customer')->group(function () {
        Route::get('/customer/dashboard', fn() => response()->json(['message' => 'Customer only']));
    });
});

/*
|--------------------------------------------------------------------------
| Vendor Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:vendor'])->group(function () {

    Route::get('/vendor/dashboard', fn() => response()->json(['message' => 'Vendor only']));

    // Vendor Orders
    Route::prefix('vendor/orders')->group(function () {
        Route::get('/', [VendorOrderController::class, 'index']);
        Route::get('/{id}', [VendorOrderController::class, 'show']);
        Route::post('/{id}/accept', [VendorOrderController::class, 'accept']);
        Route::post('/{id}/decline', [VendorOrderController::class, 'decline']);
    });

    // Vendor Inventory
    Route::prefix('vendor/inventory')->group(function () {
        Route::get('/', [VendorInventoryController::class, 'index']);
        Route::get('/{id}', [VendorInventoryController::class, 'show']);
        Route::patch('/{id}/restock', [VendorInventoryController::class, 'restock']);
        Route::patch('/{id}/prices', [VendorInventoryController::class, 'updatePrices']);
    });

    // ✅ Pay Later Decision (IMPORTANT ADDITION)
    Route::post('/orders/{orderItemId}/pay-later/accept', [PaymentController::class, 'acceptPayLater']);
    Route::post('/orders/{orderItemId}/pay-later/reject', [PaymentController::class, 'rejectPayLater']);
});

Route::get('/test-mail', function () {
    try {
        Mail::raw('Test email', function ($message) {
            $message->to('ramsingh69929@gmail.com')
                ->subject('Test');
        });
        return response()->json(['status' => 'Mail sent!']);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line'  => $e->getLine(),
        ], 500);
    }
});

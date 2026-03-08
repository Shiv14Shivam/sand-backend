<?php

use App\Http\Controllers\Api\AddressController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\MarketplaceListingController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\VendorInventoryController;
use App\Http\Controllers\Api\VendorOrderController;

/*
|--------------------------------------------------------------------------
| Category Routes
|--------------------------------------------------------------------------
*/

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Brands Routes
|--------------------------------------------------------------------------
*/

Route::get('/brands', [BrandController::class, 'index']);
Route::get('/categories/{category}/brands', [BrandController::class, 'byCategory']);
Route::get('/brands/{brand}', [BrandController::class, 'show']);
/*
|--------------------------------------------------------------------------
| Products Routes
|--------------------------------------------------------------------------
*/

Route::get('/products', [ProductController::class, 'index']);
Route::get('/brands/{brand}/products', [ProductController::class, 'byBrand']);
Route::get('/products/{product}', [ProductController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Seller Listing Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/seller/listings', [MarketplaceListingController::class, 'index']);
    Route::post('/seller/listings', [MarketplaceListingController::class, 'store']);
    Route::get('/seller/listings/{id}', [MarketplaceListingController::class, 'show']);
    Route::put('/seller/listings/{id}', [MarketplaceListingController::class, 'update']);
    Route::delete('/seller/listings/{id}', [MarketplaceListingController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Marketplace Routes
|--------------------------------------------------------------------------
*/

Route::get('/marketplace', [MarketplaceListingController::class, 'publicIndex']);

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);

    Route::post('/email/resend', [AuthController::class, 'resendVerification']);


    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::put('/addresses/{id}', [AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
        Route::post('/addresses/{id}/default', [AddressController::class, 'setDefault']);
        Route::get('/address/default', [AddressController::class, 'getDefault']);
    });
});

/*
|--------------------------------------------------------------------------
| Email Verification
|--------------------------------------------------------------------------
*/
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Email Verification
|--------------------------------------------------------------------------
*/
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Cart Routes (customer)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/',         [CartController::class, 'index']);
    Route::post('/',        [CartController::class, 'store']);
    Route::put('/{id}',     [CartController::class, 'update']);
    Route::delete('/clear', [CartController::class, 'clear']);
    Route::delete('/{id}',  [CartController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Order Routes (customer)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/',              [OrderController::class, 'history']);
    Route::get('/{id}',          [OrderController::class, 'show']);
    Route::post('/direct',       [OrderController::class, 'placeDirect']);
    Route::post('/from-cart',    [OrderController::class, 'placeFromCart']);
    Route::delete('/{id}/cancel', [OrderController::class, 'cancel']);
});

/*
|--------------------------------------------------------------------------
| Vendor Order Management (incoming order requests from customers)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor/orders')->group(function () {
    Route::get('/',             [VendorOrderController::class, 'index']);
    Route::get('/{id}',         [VendorOrderController::class, 'show']);
    Route::post('/{id}/accept', [VendorOrderController::class, 'accept']);
    Route::post('/{id}/decline', [VendorOrderController::class, 'decline']);
});

/*
|--------------------------------------------------------------------------
| Vendor Inventory Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor/inventory')->group(function () {
    Route::get('/',              [VendorInventoryController::class, 'index']);    // All listings with stock
    Route::get('/{id}',          [VendorInventoryController::class, 'show']);     // Single listing with stats
    Route::patch('/{id}/restock', [VendorInventoryController::class, 'restock']); // Add stock manually
});

/*
|--------------------------------------------------------------------------
| Role Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', fn() => response()->json(['message' => 'Admin only']));
});

Route::middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    Route::get('/vendor/dashboard', fn() => response()->json(['message' => 'Vendor only']));
});

Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', fn() => response()->json(['message' => 'Customer only']));
});

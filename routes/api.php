<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated User
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Role Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Admin only']);
    });
});

Route::middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    Route::get('/vendor/dashboard', function () {
        return response()->json(['message' => 'Vendor only']);
    });
});

Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', function () {
        return response()->json(['message' => 'Customer only']);
    });
});

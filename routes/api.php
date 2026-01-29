<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Email Verification (PUBLIC - API SAFE)
|--------------------------------------------------------------------------
*/
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {

    $user = User::findOrFail($id);

    // validate hash
    if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        return response()->json(['message' => 'Invalid verification link'], 403);
    }

    // already verified
    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified']);
    }

    // mark verified
    $user->markEmailAsVerified();
    event(new Verified($user));

    return response()->json([
        'message' => 'Email verified successfully'
    ]);
})->middleware(['signed'])->name('verification.verify');


/*
|--------------------------------------------------------------------------
| Resend Verification (AUTH REQUIRED)
|--------------------------------------------------------------------------
*/
Route::post('/email/resend', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'Email already verified'
        ], 400);
    }

    $request->user()->sendEmailVerificationNotification();

    return response()->json([
        'message' => 'Verification email sent again'
    ]);
})->middleware(['auth:sanctum', 'throttle:6,1']);

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

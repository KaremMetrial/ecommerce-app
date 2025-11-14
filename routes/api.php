<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public routes
Route::prefix('v1')->group(function () {
    // Categories
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::get('categories/tree', [CategoryController::class, 'tree']);

    // Products
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/{product}/related', [ProductController::class, 'related']);
    Route::get('products/search', [ProductController::class, 'search']);

    // Coupons (public validation)
    Route::post('coupons/validate', [CouponController::class, 'validate']);
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    // Language
    Route::get('languages', [App\Http\Controllers\Api\LanguageController::class, 'index']);
    Route::post('languages/switch', [App\Http\Controllers\Api\LanguageController::class, 'switch']);
    Route::get('languages/current', [App\Http\Controllers\Api\LanguageController::class, 'current']);
    Route::get('languages/translations', [App\Http\Controllers\Api\LanguageController::class, 'translations']);

    // Cart
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart/items', [CartController::class, 'addItem']);
    Route::put('cart/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('cart/items/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('cart', [CartController::class, 'clear']);
    Route::post('cart/coupon', [CartController::class, 'applyCoupon']);
    Route::delete('cart/coupon', [CartController::class, 'removeCoupon']);
    Route::get('cart/summary', [CartController::class, 'summary']);

    // Orders
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::get('orders/{order}/track', [OrderController::class, 'track']);

    // Wishlists
    Route::apiResource('wishlists', WishlistController::class);
    Route::get('wishlists/default', [WishlistController::class, 'getDefault']);
    Route::post('wishlists/{wishlist}/items', [WishlistController::class, 'addItem']);
    Route::delete('wishlists/{wishlist}/items/{itemId}', [WishlistController::class, 'removeItem']);
    Route::post('wishlists/{wishlist}/items/{itemId}/move-to-cart', [WishlistController::class, 'moveToCart']);
    Route::delete('wishlists/{wishlist}', [WishlistController::class, 'clear']);
    Route::get('wishlists/check-product', [WishlistController::class, 'checkProduct']);

    // Coupons (user-specific)
    Route::post('coupons/apply', [CouponController::class, 'apply']);

    // Addresses
    Route::apiResource('addresses', AddressController::class);
    Route::post('addresses/{address}/set-default', [AddressController::class, 'setDefault']);
    Route::get('addresses/type/{type}', [AddressController::class, 'getByType']);
    Route::post('addresses/validate', [AddressController::class, 'validate']);
    Route::get('addresses/countries', [AddressController::class, 'countries']);
    Route::get('addresses/countries/{countryCode}/states', [AddressController::class, 'states']);

    // Payments
    Route::apiResource('payments', PaymentController::class)->only(['index', 'show']);
    Route::post('orders/{order}/payment', [PaymentController::class, 'process']);
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund']);
    Route::post('payments/{payment}/retry', [PaymentController::class, 'retry']);
    Route::get('payments/methods', [PaymentController::class, 'methods']);
    Route::get('orders/{order}/payment/status', [PaymentController::class, 'status']);
    Route::get('orders/{order}/payment/history', [PaymentController::class, 'history']);
    Route::post('payments/calculate-fees', [PaymentController::class, 'calculateFees']);
});

// Admin routes (require authentication and admin role)
Route::middleware(['auth:sanctum', 'role:admin', 'throttle:60,1'])->prefix('v1/admin')->group(function () {
    // Categories (full CRUD)
    Route::apiResource('categories', CategoryController::class);

    // Products (full CRUD)
    Route::apiResource('products', ProductController::class);

    // Coupons (full CRUD)
    Route::apiResource('coupons', CouponController::class);
});

// Fallback route for API
Route::fallback([ErrorController::class, 'generic']);

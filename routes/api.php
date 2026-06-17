<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialiteController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\ChatbotController;
use App\Http\Controllers\Api\Customer\CouponController as CustomerCouponController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Api\Customer\ProfileController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\HolidayController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\Admin\ProductController;

use App\Http\Controllers\Api\Admin\SystemConfigController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\PayOSWebhookController;

use Illuminate\Support\Facades\Route;

// Public
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('/auth/google', [SocialiteController::class, 'redirect']);
Route::get('/auth/google/redirect', [SocialiteController::class, 'redirect']);
Route::get('/auth/google/callback', [SocialiteController::class, 'callback']);

Route::get('/products', [CustomerProductController::class, 'index']);
Route::get('/products/{slug}', [CustomerProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/holidays', [HolidayController::class, 'index']);


Route::get('/rituals', [App\Http\Controllers\Api\Customer\RitualController::class, 'index']);
Route::get('/rituals/{slug}', [App\Http\Controllers\Api\Customer\RitualController::class, 'show']);
Route::post('/chatbot', [\App\Http\Controllers\Api\Customer\ChatbotController::class, 'chat'])->middleware('throttle:20,1');
Route::get('/smart-calendar', [\App\Http\Controllers\Api\SmartCalendarController::class, 'index'])->middleware('throttle:5,1');
Route::get('/shipping-fee', function () {
    $fee = \App\Models\SystemConfig::where('key', 'shipping_fee_default')->value('value') ?? 20000;
    return response()->json(['shipping_fee' => (int) $fee]);
});

// PayOS Webhook — public, không cần auth (PayOS gửi từ server của họ)
Route::post('/payos/webhook', [PayOSWebhookController::class, 'handleWebhook']);

// Debug endpoints
Route::get('/payos/routes', function () {
    return response()->json(collect(\Route::getRoutes())->map(function ($route) {
        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
        ];
    }));
});

Route::get('/payos/logs', function () {
    $path = storage_path('logs/payos_webhook.log');
    if (!file_exists($path)) {
        return response()->json(['message' => 'No webhook log file found.']);
    }
    $content = file_get_contents($path);
    $lines = explode("\n", trim($content));
    $logs = [];
    foreach ($lines as $line) {
        if ($line) {
            $logs[] = json_decode($line, true);
        }
    }
    return response()->json(array_slice($logs, -100));
});

Route::get('/payos/system-logs', function () {
    $path = storage_path('logs/laravel.log');
    $verifyPath = storage_path('logs/verification_codes.txt');
    
    $logs = [];
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $logs = array_slice($lines, -150);
    }
    
    $verifyLogs = [];
    if (file_exists($verifyPath)) {
        $verifyContent = file_get_contents($verifyPath);
        $verifyLogs = explode("\n", trim($verifyContent));
    }
    
    return response()->json([
        'system_logs' => $logs,
        'verification_codes' => $verifyLogs
    ]);
});

Route::get('/payos/debug-orders', function () {
    return response()->json(\App\Models\Order::orderBy('id', 'desc')->take(10)->get([
        'id', 'order_code', 'payos_order_code', 'status'
    ]));
});
Route::middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// Customer
Route::middleware(['auth:sanctum', 'check.account.status', 'role:customer'])->group(function () {

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::patch('/items/{id}', [CartController::class, 'updateItem']);
        Route::delete('/items/{id}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    Route::post('/orders', [CustomerOrderController::class, 'store']);
    Route::get('/orders', [CustomerOrderController::class, 'index']);
    Route::get('/orders/{code}', [CustomerOrderController::class, 'show']);
    Route::patch('/orders/{code}/cancel', [CustomerOrderController::class, 'cancel']);
    Route::get('/orders/{code}/qr', [CustomerOrderController::class, 'showQR']);
    Route::post('/orders/{code}/renew-payment', [CustomerOrderController::class, 'renewPayment']);

    Route::get('/coupons', [CustomerCouponController::class, 'index']);
    Route::get('/coupons/available', [CustomerCouponController::class, 'available']);
    Route::post('/coupons/redeem', [CustomerCouponController::class, 'redeem']);
    Route::post('/coupons/verify', [CustomerCouponController::class, 'verify']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
});

// Admin
Route::middleware(['auth:sanctum', 'check.account.status', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/revenue', [DashboardController::class, 'revenueChart']);
    Route::get('/dashboard/users-growth', [DashboardController::class, 'usersGrowthChart']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('rituals', \App\Http\Controllers\Api\Admin\RitualController::class);
    Route::apiResource('holidays', HolidayController::class)->except(['index']);


    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::patch('/orders', [AdminOrderController::class, 'updateBatch']);
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::patch('/orders/{id}', [AdminOrderController::class, 'update']);
    Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

    Route::post('/products/{id}/images', [ProductController::class, 'uploadImages']);
    Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage']);
    Route::post('/products/{id}/images/{imageId}/set-primary', [ProductController::class, 'setPrimaryImage']);

    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users', [UserController::class, 'updateBatch']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
    Route::patch('/users/{id}/toggle-lock', [UserController::class, 'lockToggle']);
    Route::patch('/users/{id}/role', [UserController::class, 'updateRole']);

    Route::get('/config', [SystemConfigController::class, 'index']);
    Route::put('/config', [SystemConfigController::class, 'update']);

    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments/{id}/mark-paid', [PaymentController::class, 'markPaid']);

    // Quản lý mã giảm giá
    Route::apiResource('coupons', AdminCouponController::class);
});

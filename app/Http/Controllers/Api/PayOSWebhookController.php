<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PayOSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayOSWebhookController extends Controller
{
    protected PayOSService $payOSService;

    public function __construct(PayOSService $payOSService)
    {
        $this->payOSService = $payOSService;
    }

    /**
     * Nhận và xử lý Webhook từ PayOS khi thanh toán thành công.
     * Route: POST /api/payos/webhook
     */
    public function handleWebhook(Request $request)
    {
        $webhookBody = $request->all();

        // 1. Xác thực chữ ký dữ liệu — phòng chống giả mạo
        $data = $this->payOSService->verifyWebhookData($webhookBody);

        if (!$data) {
            Log::warning('[PayOS Webhook] Chữ ký không hợp lệ', ['body' => $webhookBody]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // Khi PayOS gửi webhook xác thực endpoint (test), code là 0 — bỏ qua
        $orderCode = $data['orderCode'] ?? null;
        if ($orderCode === 0 || $orderCode === null) {
            return response()->json(['message' => 'Webhook verified'], 200);
        }

        // 2. Tìm đơn hàng khớp với mã PayOS
        $order = Order::where('payos_order_code', $orderCode)
            ->with('payment')
            ->first();

        if (!$order) {
            Log::error('[PayOS Webhook] Không tìm thấy đơn hàng', ['payos_order_code' => $orderCode]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 3. Chỉ cập nhật nếu đơn đang ở trạng thái pending (tránh xử lý trùng)
        if ($order->status === 'pending' && isset($data['code']) && $data['code'] === '00') {
            DB::transaction(function () use ($order, $data) {
                $order->status = 'processing';
                $order->save();

                if ($order->payment) {
                    $order->payment->status   = 'paid';
                    $order->payment->paid_at  = now();
                    $order->payment->save();
                }
            });

            Log::info("[PayOS Webhook] Đơn hàng #{$order->order_code} đã thanh toán tự động thành công.");
        }

        return response()->json(['message' => 'Success'], 200);
    }
}

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
        
        // Ghi log ra file tuỳ chỉnh để phục vụ debug trên production
        $logData = [
            'time' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'body' => $webhookBody,
        ];
        @file_put_contents(storage_path('logs/payos_webhook.log'), json_encode($logData) . "\n", FILE_APPEND);

        Log::info('[PayOS Webhook] Nhận request webhook từ PayOS', [
            'ip'   => $request->ip(),
            'body' => $webhookBody
        ]);

        try {
            // 1. Xác thực chữ ký dữ liệu — phòng chống giả mạo
            $data = $this->payOSService->verifyWebhookData($webhookBody);

            if (!$data) {
                Log::warning('[PayOS Webhook] Xác thực chữ ký THẤT BẠI', ['body' => $webhookBody]);
                return response()->json(['message' => 'Invalid signature'], 400);
            }

            Log::info('[PayOS Webhook] Xác thực chữ ký thành công', ['verified_data' => $data]);

            // Khi PayOS gửi webhook xác thực endpoint (test), code là 0 hoặc 123 — bỏ qua và trả về 200
            $orderCode = $data['orderCode'] ?? null;
            $description = $data['description'] ?? '';
            if ($orderCode === 0 || $orderCode === 123 || $orderCode === null || $description === 'VQRIO123') {
                Log::info('[PayOS Webhook] Nhận test webhook xác thực endpoint.', ['orderCode' => $orderCode]);
                return response()->json(['message' => 'Webhook verified'], 200);
            }

            // 2. Tìm đơn hàng khớp với mã PayOS
            $order = Order::where('payos_order_code', $orderCode)
                ->with('payment')
                ->first();

            if (!$order) {
                Log::warning('[PayOS Webhook] Không tìm thấy đơn hàng khớp với payos_order_code, phản hồi 200 để tránh PayOS thử lại', [
                    'payos_order_code' => $orderCode
                ]);
                return response()->json(['message' => 'Order not found but webhook acknowledged'], 200);
            }

            Log::info('[PayOS Webhook] Đã tìm thấy đơn hàng tương ứng', [
                'order_id'   => $order->id,
                'order_code' => $order->order_code,
                'status'     => $order->status
            ]);

            // 3. Chỉ cập nhật nếu thanh toán chưa được xác nhận hoàn tất (tránh xử lý trùng)
            if ($order->payment && $order->payment->status === 'pending') {
                if (isset($data['code']) && $data['code'] === '00') {
                    DB::transaction(function () use ($order) {
                        // Trạng thái đơn hàng giữ nguyên là pending (Chờ xác nhận)
                        // Chỉ cập nhật trạng thái thanh toán sang paid (Đã thanh toán)
                        if ($order->payment) {
                            $order->payment->status   = 'paid';
                            $order->payment->paid_at  = now();
                            $order->payment->save();
                        }
                    });

                    Log::info("[PayOS Webhook] Cập nhật trạng thái thanh toán đơn hàng #{$order->order_code} sang ĐÃ THANH TOÁN thành công. Trạng thái đơn hàng giữ nguyên là pending.");
                } else {
                    Log::warning("[PayOS Webhook] Thanh toán không thành công hoặc mã code khác 00", [
                        'code' => $data['code'] ?? null
                    ]);
                }
            } else {
                Log::info("[PayOS Webhook] Thanh toán đơn hàng #{$order->order_code} đã ở trạng thái [" . ($order->payment ? $order->payment->status : 'none') . "], bỏ qua cập nhật.");
            }

            return response()->json(['message' => 'Success'], 200);
        } catch (\Exception $e) {
            Log::error('[PayOS Webhook] Lỗi hệ thống khi xử lý webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}

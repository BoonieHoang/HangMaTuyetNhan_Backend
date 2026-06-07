<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    /**
     * Danh sách các đơn hàng đang chờ hoàn tiền.
     * GET /api/admin/refunds
     */
    public function index(Request $request)
    {
        $query = Payment::with(['order.user', 'order.items'])
            ->whereIn('status', ['refund_pending', 'refunded']);

        if ($request->status && in_array($request->status, ['refund_pending', 'refunded'])) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderByRaw("FIELD(status, 'refund_pending', 'refunded')")
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json($payments);
    }

    /**
     * Admin duyệt hoàn tiền thành công.
     * POST /api/admin/refunds/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'refund_note' => 'nullable|string|max:500',
        ]);

        $payment = Payment::with('order')->findOrFail($id);

        if ($payment->status !== 'refund_pending') {
            return response()->json([
                'message' => 'Thanh toán này không ở trạng thái chờ hoàn tiền.',
            ], 400);
        }

        DB::transaction(function () use ($payment, $request) {
            $payment->update([
                'status'      => 'refunded',
                'refund_note' => $request->refund_note ?? 'Admin đã xác nhận hoàn tiền.',
                'refunded_at' => now(),
            ]);

            Log::info('[Refund] Admin đã duyệt hoàn tiền cho đơn hàng', [
                'order_code' => $payment->order->order_code ?? 'N/A',
                'amount'     => $payment->amount,
                'admin_id'   => $request->user()->id,
            ]);
        });

        return response()->json([
            'message' => 'Đã duyệt hoàn tiền thành công.',
            'payment' => $payment->fresh(),
        ]);
    }

    /**
     * Admin từ chối yêu cầu hoàn tiền.
     * POST /api/admin/refunds/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'refund_note' => 'required|string|max:500',
        ]);

        $payment = Payment::with('order')->findOrFail($id);

        if ($payment->status !== 'refund_pending') {
            return response()->json([
                'message' => 'Thanh toán này không ở trạng thái chờ hoàn tiền.',
            ], 400);
        }

        DB::transaction(function () use ($payment, $request) {
            $payment->update([
                'status'      => 'paid', // Giữ lại trạng thái paid nếu từ chối
                'refund_note' => $request->refund_note,
            ]);

            Log::info('[Refund] Admin đã từ chối hoàn tiền cho đơn hàng', [
                'order_code' => $payment->order->order_code ?? 'N/A',
                'reason'     => $request->refund_note,
                'admin_id'   => $request->user()->id,
            ]);
        });

        return response()->json([
            'message' => 'Đã từ chối yêu cầu hoàn tiền.',
            'payment' => $payment->fresh(),
        ]);
    }
}

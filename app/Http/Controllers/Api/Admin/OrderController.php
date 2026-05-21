<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('fullname', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return OrderResource::collection($query->latest()->paginate(15));
    }

    public function show($id)
    {
        $order = Order::with(['user', 'items', 'payment'])->findOrFail($id);
        return new OrderResource($order);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::with('payment')->findOrFail($id);

        if (in_array($order->status, ['cancelled', 'delivered'])) {
            return response()->json([
                'message' => 'Không thể cập nhật trạng thái cho đơn hàng đã hủy hoặc đã hoàn thành.'
            ], 422);
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipping,delivered,cancelled',
            'cancelled_reason' => 'required_if:status,cancelled|string|nullable',
        ]);

        $newStatus = $request->status;

        if ($newStatus === 'cancelled') {
            $order->status = 'cancelled';
            $order->cancelled_reason = $request->cancelled_reason;

            if ($order->payment && $order->payment_method === 'bank_transfer' && $order->payment->status === 'pending') {
                $order->payment->update(['status' => 'failed']);
            }
        } else {
            $order->status = $newStatus;
            
            // Automatically mark as paid only if status is "delivered" (Hoàn thành)
            if ($newStatus === 'delivered' && $order->payment && $order->payment->status !== 'paid') {
                $order->payment->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);
            }
        }

        $order->save();

        return new OrderResource($order);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('order');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        return PaymentResource::collection($query->latest()->paginate(15));
    }

    public function show($id)
    {
        $payment = Payment::with('order')->findOrFail($id);
        return new PaymentResource($payment);
    }

    public function markPaid($id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        if ($payment->status === 'paid') {
            abort(422, 'Thanh toán này đã được xác nhận.');
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        if ($payment->order && $payment->order->status === 'pending') {
            $payment->order->update(['status' => 'confirmed']);
        }

        return new PaymentResource($payment);
    }
}

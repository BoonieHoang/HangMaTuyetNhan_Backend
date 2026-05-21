<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $cartService;
    protected $orderCodeService;
    protected $vietQRService;

    public function __construct(\App\Services\CartService $cartService, \App\Services\OrderCodeService $orderCodeService, \App\Services\VietQRService $vietQRService)
    {
        $this->cartService = $cartService;
        $this->orderCodeService = $orderCodeService;
        $this->vietQRService = $vietQRService;
    }

    public function index(Request $request)
    {
        $orders = Order::with('payment')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, $code)
    {
        $order = Order::with(['items', 'payment'])
            ->where('user_id', $request->user()->id)
            ->where('order_code', $code)
            ->firstOrFail();

        return new OrderResource($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'ship_address' => 'required|string',
            'payment_method' => 'required|in:cod,bank_transfer',
        ]);

        $cart = $this->cartService->getOrCreateCart($request);
        $cart->load('items.product.images');

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Giỏ hàng của bạn đang trống'], 422);
        }

        foreach ($cart->items as $item) {
            if ($item->product->stock < $item->quantity) {
                return response()->json(['message' => "Sản phẩm {$item->product->name} không đủ số lượng trong kho"], 422);
            }
        }

        $order = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $cart) {
            $subtotal = $this->cartService->getCartTotal($cart);
            $shippingFee = $request->shipping_method === 'ship' ? 15000 : 0;

            $order = Order::create([
                'order_code' => $this->orderCodeService->generate(),
                'user_id' => $request->user()->id,
                'fullname' => $request->fullname,
                'phone' => $request->phone,
                'email' => $request->user()->email,
                'ship_address' => $request->ship_address,
                'note' => $request->note,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $subtotal + $shippingFee,
            ]);

            $transferContent = $order->order_code;
            $qrUrl = null;

            if ($request->payment_method === 'bank_transfer') {
                $qrUrl = $this->vietQRService->generateQRUrl($order->total_amount, $transferContent);
            }

            \App\Models\Payment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'amount' => $order->total_amount,
                'transfer_content' => $request->payment_method === 'bank_transfer' ? $transferContent : null,
                'qr_url' => $qrUrl,
            ]);

            foreach ($cart->items as $item) {
                $primaryImage = null;
                if ($item->product->relationLoaded('images')) {
                    $primary = $item->product->images->where('is_primary', true)->first();
                    $primaryImage = $primary ? $primary->image_url : ($item->product->images->first()->image_url ?? null);
                }

                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_image' => $primaryImage,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->quantity * $item->unit_price,
                ]);

                $item->product->decrement('stock', $item->quantity);
            }

            $this->cartService->clear($cart);

            return $order;
        });

        $order->load(['items', 'payment']);

        return response()->json([
            'order' => new OrderResource($order),
            'qr_url' => $order->payment->qr_url ?? null,
        ], 201);
    }

    public function showQR($code)
    {
        $order = Order::where('order_code', $code)->with('payment')->firstOrFail();

        return response()->json([
            'qr_url' => $order->payment->qr_url ?? null,
        ]);
    }

    public function cancel(Request $request, $code)
    {
        $order = Order::where('user_id', $request->user()->id)
            ->where('order_code', $code)
            ->with(['items.product', 'payment'])
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Chỉ có thể hủy đơn hàng ở trạng thái chờ xử lý.',
            ], 400);
        }

        $request->validate([
            'cancelled_reason' => 'required|string|max:255',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $request) {
            $order->status = 'cancelled';
            $order->cancelled_reason = $request->cancelled_reason;
            $order->save();

            if ($order->payment && $order->payment->status === 'pending') {
                $order->payment->update(['status' => 'failed']);
            }

            // Restore stock
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }
        });

        return response()->json([
            'message' => 'Hủy đơn hàng thành công.',
            'order' => new OrderResource($order),
        ]);
    }
}

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
    protected $payOSService;

    public function __construct(\App\Services\CartService $cartService, \App\Services\OrderCodeService $orderCodeService, \App\Services\PayOSService $payOSService)
    {
        $this->cartService = $cartService;
        $this->orderCodeService = $orderCodeService;
        $this->payOSService = $payOSService;
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
            'fullname'       => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'ship_address'   => 'required|string',
            'payment_method' => 'required|in:cod,bank_transfer',
            'coupon_id'      => 'nullable|exists:coupons,id',
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
            $subtotal    = $this->cartService->getCartTotal($cart);
            $defaultFee  = (int) (\App\Models\SystemConfig::where('key', 'shipping_fee_default')->value('value') ?? 20000);
            $shippingFee = $request->shipping_method === 'ship' ? $defaultFee : 0;

            // Xử lý mã giảm giá
            $discountAmount = 0;
            $couponId       = null;
            if ($request->filled('coupon_id')) {
                $userCoupon = \App\Models\UserCoupon::with('coupon')
                    ->where('user_id', $request->user()->id)
                    ->where('coupon_id', $request->coupon_id)
                    ->where('is_used', false)
                    ->first();

                if ($userCoupon && $userCoupon->coupon->isValid() && $subtotal >= $userCoupon->coupon->min_order_amount) {
                    $discountAmount = $userCoupon->coupon->calculateDiscount($subtotal);
                    $couponId       = $userCoupon->coupon->id;
                }
            }

            $totalAmount = max(0, $subtotal + $shippingFee - $discountAmount);

            $order = Order::create([
                'order_code'      => $this->orderCodeService->generate(),
                'user_id'         => $request->user()->id,
                'fullname'        => $request->fullname,
                'phone'           => $request->phone,
                'email'           => $request->user()->email ?? '',
                'ship_address'    => $request->ship_address,
                'note'            => $request->note,
                'coupon_id'       => $couponId,
                'payment_method'  => $request->payment_method,
                'status'          => 'pending',
                'subtotal'        => $subtotal,
                'shipping_fee'    => $shippingFee,
                'discount_amount' => $discountAmount,
                'total_amount'    => $totalAmount,
            ]);

            // Đánh dấu mã giảm giá đã dùng
            if ($couponId && isset($userCoupon)) {
                $userCoupon->update(['is_used' => true, 'used_at' => now()]);
                \App\Models\Coupon::where('id', $couponId)->increment('used_count');
            }

            $transferContent = 'TToan ' . $order->order_code;
            $payosCheckoutUrl = null;
            $payosQrCode = null;

            if ($request->payment_method === 'bank_transfer') {
                try {
                    $paymentLink = $this->payOSService->createPaymentLink(
                        $order->order_code,
                        (int) $order->total_amount,
                        $transferContent
                    );
                    $payosCheckoutUrl = $paymentLink['checkoutUrl'] ?? null;
                    $payosQrCode     = $paymentLink['qrCode'] ?? null;

                    // Lưu numeric code PayOS để đối chiếu Webhook
                    $numericCode = (int) preg_replace('/\D/', '', $order->order_code);
                    $order->payos_order_code = $numericCode;
                    $order->save();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('[Order] Tạo link PayOS thất bại', [
                        'order_code' => $order->order_code,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            \App\Models\Payment::create([
                'order_id'           => $order->id,
                'payment_method'     => $request->payment_method,
                'status'             => 'pending',
                'amount'             => $order->total_amount,
                'transfer_content'   => $request->payment_method === 'bank_transfer' ? $transferContent : null,
                'payos_checkout_url' => $payosCheckoutUrl,
                'payos_qr_code'      => $payosQrCode,
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
            'order'              => new OrderResource($order),
            'payos_checkout_url' => $order->payment->payos_checkout_url ?? null,
            'payos_qr_code'      => $order->payment->payos_qr_code ?? null,
        ], 201);
    }

    public function showQR($code)
    {
        $order = Order::where('order_code', $code)->with('payment')->firstOrFail();

        return response()->json([
            'payos_checkout_url' => $order->payment->payos_checkout_url ?? null,
            'payos_qr_code'      => $order->payment->payos_qr_code ?? null,
        ]);
    }

    public function renewPayment(Request $request, $code)
    {
        $order = Order::where('user_id', $request->user()->id)
            ->where('order_code', $code)
            ->with('payment')
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Đơn hàng không ở trạng thái chờ xử lý.'], 400);
        }

        if ($order->payment_method !== 'bank_transfer') {
            return response()->json(['message' => 'Đơn hàng này không thanh toán qua chuyển khoản.'], 400);
        }

        if (!$order->payment || $order->payment->status !== 'pending') {
            return response()->json(['message' => 'Không thể tạo lại link thanh toán.'], 400);
        }

        try {
            $transferContent = 'TToan ' . $order->order_code;
            
            // Tạo mã payos_order_code duy nhất bằng timestamp + id để tránh trùng lặp trên PayOS
            $newPayosOrderCode = (int) (time() . $order->id);

            $paymentLink = $this->payOSService->createPaymentLink(
                (string) $newPayosOrderCode,
                (int) $order->total_amount,
                $transferContent
            );

            $checkoutUrl = $paymentLink['checkoutUrl'] ?? null;
            $qrCode      = $paymentLink['qrCode'] ?? null;

            // Cập nhật mã PayOS mới và link mới vào DB
            $order->payos_order_code = $newPayosOrderCode;
            $order->save();

            $order->payment->update([
                'payos_checkout_url' => $checkoutUrl,
                'payos_qr_code'      => $qrCode,
            ]);

            return response()->json([
                'payos_checkout_url' => $checkoutUrl,
                'payos_qr_code'      => $qrCode,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[Order] Tạo lại link PayOS thất bại', [
                'order_code' => $order->order_code,
                'error'      => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Không thể tạo link thanh toán: ' . $e->getMessage()
            ], 500);
        }
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

        // Không cho hủy nếu đã thanh toán bằng PayOS thành công
        if ($order->payment && $order->payment->status === 'paid') {
            return response()->json([
                'message' => 'Đơn hàng đã thanh toán thành công, không thể hủy.',
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

            // Hoàn lại tồn kho
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }
        });
        return response()->json([
            'message' => 'Hủy đơn hàng thành công.',
            'order'   => new OrderResource($order->fresh(['items', 'payment'])),        ]);
    }
}

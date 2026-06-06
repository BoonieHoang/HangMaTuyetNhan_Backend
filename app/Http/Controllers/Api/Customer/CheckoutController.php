<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\OrderCodeService;
use App\Services\PayOSService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    protected $cartService;
    protected $orderCodeService;
    protected $payOSService;

    public function __construct(CartService $cartService, OrderCodeService $orderCodeService, PayOSService $payOSService)
    {
        $this->cartService = $cartService;
        $this->orderCodeService = $orderCodeService;
        $this->payOSService = $payOSService;
    }

    public function store(PlaceOrderRequest $request)
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $cart->load('items.product.images');

        if ($cart->items->isEmpty()) {
            abort(422, 'Giỏ hàng của bạn đang trống');
        }

        foreach ($cart->items as $item) {
            if ($item->product->stock < $item->quantity) {
                abort(422, "Sản phẩm {$item->product->name} không đủ số lượng trong kho");
            }
        }

        $order = DB::transaction(function () use ($request, $cart) {
            $totalAmount = $this->cartService->getCartTotal($cart);
            $shippingFee = 0; // Configurable

            $order = Order::create([
                'order_code' => $this->orderCodeService->generate(),
                'user_id' => $request->user()->id,
                'fullname' => $request->fullname,
                'phone' => $request->phone,
                'email' => $request->email,
                'ship_address' => $request->ship_address,
                'note' => $request->note,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'subtotal' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount + $shippingFee,
            ]);

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

                    // Lưu numeric code PayOS vào đơn hàng để đối chiếu Webhook
                    $numericCode = (int) preg_replace('/\D/', '', $order->order_code);
                    $order->payos_order_code = $numericCode;
                    $order->save();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('[Checkout] Tạo link PayOS thất bại', [
                        'order_code' => $order->order_code,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            Payment::create([
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

                $subtotal = $item->quantity * $item->unit_price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_image' => $primaryImage,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $subtotal,
                ]);

                $item->product->decrement('stock', $item->quantity);
            }

            $this->cartService->clear($cart);

            return $order;
        });

        $order->load(['items', 'payment']);

        return response()->json([
            'order'               => new OrderResource($order),
            'payos_checkout_url'  => $order->payment->payos_checkout_url ?? null,
            'payos_qr_code'       => $order->payment->payos_qr_code ?? null,
        ], 201);
    }
}

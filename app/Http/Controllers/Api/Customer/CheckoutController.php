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
use App\Services\VietQRService;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    protected $cartService;
    protected $orderCodeService;
    protected $vietQRService;

    public function __construct(CartService $cartService, OrderCodeService $orderCodeService, VietQRService $vietQRService)
    {
        $this->cartService = $cartService;
        $this->orderCodeService = $orderCodeService;
        $this->vietQRService = $vietQRService;
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

            $transferContent = $order->order_code;
            $qrUrl = null;

            if ($request->payment_method === 'bank_transfer') {
                $qrUrl = $this->vietQRService->generateQRUrl($order->total_amount, $transferContent);
            }

            Payment::create([
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
            'order' => new OrderResource($order),
            'qr_url' => $order->payment->qr_url ?? null,
        ], 201);
    }

    public function getCartQR(Request $request)
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $total = $this->cartService->getCartTotal($cart);
        $shippingMethod = $request->query('shipping_method', 'ship');
        $shippingFee = $shippingMethod === 'ship' ? 15000 : 0;

        $qrUrl = $this->vietQRService->generateQRUrl($total + $shippingFee, "THANH TOAN DON HANG");

        return response()->json(['qr_url' => $qrUrl]);
    }
}

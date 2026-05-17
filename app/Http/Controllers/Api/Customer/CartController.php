<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $cart->load('items.product.images');
        
        return new CartResource($cart);
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->cartService->getOrCreateCart($request);
        $this->cartService->addItem($cart, $request->product_id, $request->quantity);

        $cart->load('items.product.images');
        return new CartResource($cart);
    }

    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->cartService->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($id);

        $this->cartService->updateItem($item, $request->quantity);

        $cart->load('items.product.images');
        return new CartResource($cart);
    }

    public function removeItem(Request $request, $id)
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($id);

        $this->cartService->removeItem($item);

        return response()->json(['message' => 'Đã xóa sản phẩm khỏi giỏ hàng']);
    }

    public function clear(Request $request)
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $this->cartService->clear($cart);

        return response()->json(['message' => 'Đã xóa toàn bộ giỏ hàng']);
    }
}

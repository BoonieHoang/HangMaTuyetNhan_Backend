<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class CartService
{
    public function getOrCreateCart(Request $request): Cart
    {
        $userId = $request->user() ? $request->user()->id : null;
        $sessionId = $request->header('X-Session-ID');

        if ($userId) {
            return Cart::firstOrCreate(['user_id' => $userId]);
        }

        if (! $sessionId) {
            $sessionId = uniqid('sess_', true);
        }

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    public function addItem(Cart $cart, $productId, $quantity): CartItem
    {
        $product = Product::findOrFail($productId);
        
        if ($product->stock < $quantity) {
            abort(422, 'Sản phẩm không đủ số lượng trong kho');
        }

        $cartItem = $cart->items()->where('product_id', $productId)->first();

        $price = $product->price;

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            if ($product->stock < $newQuantity) {
                abort(422, 'Sản phẩm không đủ số lượng trong kho');
            }
            $cartItem->update([
                'quantity' => $newQuantity,
                'unit_price' => $price,
            ]);
        } else {
            $cartItem = $cart->items()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $price,
            ]);
        }

        return $cartItem;
    }

    public function updateItem(CartItem $item, $quantity): CartItem
    {
        $product = $item->product;

        if ($product->stock < $quantity) {
            abort(422, 'Sản phẩm không đủ số lượng trong kho');
        }

        $item->update(['quantity' => $quantity]);

        return $item;
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function getCartTotal(Cart $cart): float
    {
        return $cart->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });
    }

    public function mergeGuestCart($sessionId, User $user): void
    {
        if (!$sessionId) return;

        $guestCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->first();
        if (!$guestCart) return;

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $guestItem) {
            $userItem = $userCart->items()->where('product_id', $guestItem->product_id)->first();
            
            if ($userItem) {
                $userItem->update([
                    'quantity' => $userItem->quantity + $guestItem->quantity,
                ]);
            } else {
                $guestItem->update(['cart_id' => $userCart->id]);
            }
        }

        $guestCart->delete();
    }
}

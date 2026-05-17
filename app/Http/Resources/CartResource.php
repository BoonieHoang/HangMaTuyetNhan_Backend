<?php

namespace App\Http\Resources;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request)
    {
        $cartService = app(CartService::class);
        
        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'total_items' => $this->items()->sum('quantity') ?? 0,
            'total_amount' => number_format($this->items ? $cartService->getCartTotal($this->resource) : 0, 0, ',', '.'),
        ];
    }
}

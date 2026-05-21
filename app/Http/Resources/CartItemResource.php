<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray($request)
    {
        $product = $this->whenLoaded('product');
        $primaryImage = null;

        if ($product && $product->relationLoaded('images')) {
            $primary = $product->images->where('is_primary', true)->first();
            $primaryImage = $primary ? $primary->image_url : ($product->images->first()->image_url ?? null);
        }

        return [
            'id' => $this->id,
            'product' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => number_format($product->price, 0, ',', '.'),
                'primary_image' => $primaryImage,
            ] : null,
            'quantity' => $this->quantity,
            'unit_price' => number_format($this->unit_price, 0, ',', '.'),
            'subtotal' => number_format($this->quantity * $this->unit_price, 0, ',', '.'),
        ];
    }
}

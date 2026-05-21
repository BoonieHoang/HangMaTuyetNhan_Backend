<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => (int) $this->price,
            'stock' => $this->stock,
            'holidays' => $this->whenLoaded('holidays', function() {
                return $this->holidays->map(fn($h) => ['id' => $h->id, 'name' => $h->name]);
            }),
            'purposes' => $this->whenLoaded('purposes', function() {
                return $this->purposes->map(fn($p) => ['id' => $p->id, 'name' => $p->name]);
            }),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'views' => $this->views,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => $this->whenLoaded('images', function () {
                return $this->images->sortBy('sort_order')->values();
            }),
            'primary_image' => $this->whenLoaded('images', function () {
                $primary = $this->images->where('is_primary', true)->first();
                return $primary ? $primary->image_url : ($this->images->first()->image_url ?? null);
            }),
            'created_at' => $this->created_at,
        ];
    }
}

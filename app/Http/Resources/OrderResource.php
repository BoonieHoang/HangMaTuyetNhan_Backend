<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'fullname' => $this->fullname,
            'phone' => $this->phone,
            'email' => $this->email,
            'ship_address' => $this->ship_address,
            'note' => $this->note,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'subtotal' => $this->subtotal,
            'shipping_fee' => $this->shipping_fee,
            'total_amount' => $this->total_amount,
            'cancelled_reason' => $this->cancelled_reason,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'created_at' => $this->created_at,
        ];
    }
}

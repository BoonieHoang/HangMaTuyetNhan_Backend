<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'payment_method'      => $this->payment_method,
            'status'              => $this->status,
            'amount'              => $this->amount,
            'transfer_content'    => $this->transfer_content,
            'payos_checkout_url'  => $this->payos_checkout_url,
            'payos_qr_code'       => $this->payos_qr_code,
            'paid_at'             => $this->paid_at,
            'refund_reason'       => $this->refund_reason,
            'refund_note'         => $this->refund_note,
            'refunded_at'         => $this->refunded_at,
        ];
    }
}

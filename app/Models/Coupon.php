<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount',
        'points_cost',
        'usage_limit',
        'used_count',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'discount_value'   => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount'     => 'decimal:2',
        'is_active'        => 'boolean',
        'expires_at'       => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }

    /**
     * Tính số tiền giảm thực tế dựa trên subtotal đơn hàng.
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->discount_type === 'percent') {
            $discount = $subtotal * ($this->discount_value / 100);
            if ($this->max_discount) {
                $discount = min($discount, (float) $this->max_discount);
            }
        } else {
            $discount = (float) $this->discount_value;
        }
        return min($discount, $subtotal);
    }

    /**
     * Kiểm tra mã có thể dùng được không.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        return true;
    }
}

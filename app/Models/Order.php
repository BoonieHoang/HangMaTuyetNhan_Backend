<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_code',
        'payos_order_code',
        'user_id',
        'fullname',
        'phone',
        'email',
        'ship_address',
        'note',
        'coupon_id',
        'payment_method',
        'status',
        'subtotal',
        'shipping_fee',
        'discount_amount',
        'total_amount',
        'points_earned',
        'cancelled_reason',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'shipping_fee'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'processing' => 'Đang xử lý',
            'shipping' => 'Đang giao hàng',
            'delivered' => 'Đã giao hàng',
            'cancelled' => 'Đã hủy',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}

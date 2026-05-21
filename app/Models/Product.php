<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'price',
        'stock',
        'description',
        'is_active',
        'views',
    ];

    protected $casts = [
        'price'             => 'decimal:2',
        'is_active'         => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }



    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function holidays()
    {
        return $this->belongsToMany(Holiday::class, 'product_holiday');
    }

    public function purposes()
    {
        return $this->belongsToMany(Purpose::class, 'product_purpose');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            return $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('sku', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }
        return $query;
    }

    public function scopeFilterCategory($query, $categoryId)
    {
        if ($categoryId) {
            return $query->where('category_id', $categoryId);
        }
        return $query;
    }

    public function scopeFilterHoliday($query, $holidayId)
    {
        if ($holidayId) {
            return $query->whereHas('holidays', function($q) use ($holidayId) {
                $q->where('holidays.id', $holidayId);
            });
        }
        return $query;
    }

    public function scopeFilterPurpose($query, $purposeId)
    {
        if ($purposeId) {
            return $query->whereHas('purposes', function($q) use ($purposeId) {
                $q->where('purposes.id', $purposeId);
            });
        }
        return $query;
    }

    public function scopeFilterPrice($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }



    // ─── Accessors ─────────────────────────────────────────────────────────────

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primary = $this->images->firstWhere('is_primary', true)
                   ?? $this->images->first();
        return $primary?->image_url;
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) $this->price;
    }
}

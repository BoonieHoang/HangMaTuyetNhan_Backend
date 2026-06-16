<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['name', 'description', 'ritual_slug'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_holiday');
    }
}

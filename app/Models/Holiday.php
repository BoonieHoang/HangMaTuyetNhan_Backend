<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['name', 'description', 'ritual_slug'];

    /**
     * ritual_slug stores a JSON array of {slug, title} objects.
     * e.g. [{"slug":"le-ta-mo","title":"Lễ Tạ Mộ"},...]
     */
    protected $casts = [
        'ritual_slug' => 'array',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_holiday');
    }
}

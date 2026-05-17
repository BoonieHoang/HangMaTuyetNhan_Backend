<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;

class OrderCodeService
{
    public function generate(): string
    {
        $today = Carbon::today();
        $datePrefix = $today->format('Ymd');
        
        $count = Order::whereDate('created_at', $today)->count();
        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        return "ORD-{$datePrefix}-{$sequence}";
    }
}

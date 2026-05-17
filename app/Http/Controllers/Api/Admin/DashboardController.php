<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    public function stats()
    {
        return response()->json([
            'total_revenue' => $this->analytics->totalRevenue(),
            'total_orders' => $this->analytics->totalOrders(),
            'pending_orders' => $this->analytics->totalOrders('pending'),
            'total_customers' => $this->analytics->totalUsers(),
            'total_products' => Product::count(),
            'order_status_breakdown' => $this->analytics->orderStatusBreakdown(),
            'top_products' => $this->analytics->topProducts(),
        ]);
    }

    public function revenueChart(Request $request)
    {
        $period = $request->query('period', '7d');
        return response()->json([
            'period' => $period,
            'data' => $this->analytics->revenueByPeriod($period),
        ]);
    }

    public function usersGrowthChart(Request $request)
    {
        $period = $request->query('period', '7d');
        return response()->json([
            'period' => $period,
            'data' => $this->analytics->usersGrowthByPeriod($period),
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function totalRevenue(): float
    {
        return (float) Payment::where('status', 'paid')->sum('amount');
    }

    public function revenueByPeriod(string $period): array
    {
        $query = Payment::where('status', 'paid');
        $format = '%Y-%m-%d';
        $groupBy = 'date';
        
        switch ($period) {
            case '7d':
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                break;
            case '30d':
                $startDate = Carbon::now()->subDays(29)->startOfDay();
                break;
            case '12m':
                $startDate = Carbon::now()->subMonths(11)->startOfMonth();
                $format = '%Y-%m';
                $groupBy = 'month';
                break;
            default:
                $startDate = Carbon::now()->subDays(6)->startOfDay();
        }

        $payments = $query->where('paid_at', '>=', $startDate)->get();

        $data = [];
        if ($period === '12m') {
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i)->format('Y-m');
                $data[$month] = 0;
            }
            foreach ($payments as $payment) {
                $month = Carbon::parse($payment->paid_at)->format('Y-m');
                if (isset($data[$month])) {
                    $data[$month] += $payment->amount;
                }
            }
        } else {
            $days = $period === '30d' ? 29 : 6;
            for ($i = $days; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $data[$date] = 0;
            }
            foreach ($payments as $payment) {
                $date = Carbon::parse($payment->paid_at)->format('Y-m-d');
                if (isset($data[$date])) {
                    $data[$date] += $payment->amount;
                }
            }
        }

        $result = [];
        foreach ($data as $date => $revenue) {
            $result[] = ['date' => $date, 'revenue' => $revenue];
        }

        return $result;
    }

    public function totalOrders(?string $status = null): int
    {
        $query = Order::query();
        if ($status) {
            $query->where('status', $status);
        }
        return $query->count();
    }

    public function totalUsers(): int
    {
        return User::where('role', 'customer')->count();
    }

    public function topProducts(int $limit = 5)
    {
        $topItems = OrderItem::select('product_id', 'product_name', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();

        $productIds = $topItems->pluck('product_id');
        $products = \App\Models\Product::whereIn('id', $productIds)->with('images')->get()->keyBy('id');

        return $topItems->map(function ($item) use ($products) {
            $product = $products->get($item->product_id);
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'total_sold' => (int) $item->total_sold,
                'primary_image' => $product ? $product->primary_image_url : null,
                'price' => $product ? $product->price : 0,
            ];
        });
    }

    public function orderStatusBreakdown(): array
    {
        $statuses = Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return $statuses;
    }

    public function usersGrowthByPeriod(string $period): array
    {
        $query = User::where('role', 'customer');
        $format = '%Y-%m-%d';
        $groupBy = 'date';
        
        switch ($period) {
            case '7d':
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                break;
            case '30d':
                $startDate = Carbon::now()->subDays(29)->startOfDay();
                break;
            case '12m':
                $startDate = Carbon::now()->subMonths(11)->startOfMonth();
                $format = '%Y-%m';
                $groupBy = 'month';
                break;
            default:
                $startDate = Carbon::now()->subDays(6)->startOfDay();
        }

        $users = $query->where('created_at', '>=', $startDate)->get();

        $data = [];
        if ($period === '12m') {
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i)->format('Y-m');
                $data[$month] = 0;
            }
            foreach ($users as $user) {
                $month = Carbon::parse($user->created_at)->format('Y-m');
                if (isset($data[$month])) {
                    $data[$month]++;
                }
            }
        } else {
            $days = $period === '30d' ? 29 : 6;
            for ($i = $days; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $data[$date] = 0;
            }
            foreach ($users as $user) {
                $date = Carbon::parse($user->created_at)->format('Y-m-d');
                if (isset($data[$date])) {
                    $data[$date]++;
                }
            }
        }

        $result = [];
        foreach ($data as $date => $count) {
            $result[] = ['date' => $date, 'count' => $count];
        }

        return $result;
    }
}

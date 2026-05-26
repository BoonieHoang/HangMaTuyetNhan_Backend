<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmartCalendarController extends Controller
{
    public function index(OpenAIService $aiService)
    {
        try {
            // Sử dụng cache_v3 để cập nhật dữ liệu ngày lễ AI và sản phẩm mới
            $data = Cache::remember('smart_calendar_v3', 43200, function () use ($aiService) {
                // 1. Đề cử danh sách ngày lễ lịch âm gần nhất từ AI
                $aiHolidays = $aiService->getUpcomingLunarHolidays(3);

                if (empty($aiHolidays)) {
                    return [];
                }

                // 2. Lấy danh sách sản phẩm mới nhất đang hoạt động từ DB
                $newProducts = Product::active()
                    ->with(['images', 'category'])
                    ->orderBy('created_at', 'desc')
                    ->limit(4)
                    ->get();

                $productsData = $newProducts->map(fn($p) => [
                    'id'    => $p->id,
                    'name'  => $p->name,
                    'slug'  => $p->slug,
                    'price' => (int) $p->price,
                    'image' => $p->primary_image_url,
                ])->values()->toArray();

                // 3. Kết xuất thông tin ngày lễ động kết hợp với sản phẩm mới
                return collect($aiHolidays)
                    ->map(function ($item) use ($productsData) {
                        return [
                            'id'          => null,
                            'name'        => $item['name'] ?? '',
                            'lunarLabel'  => $item['lunarLabel'] ?? '',
                            'nextDate'    => $item['nextDate'] ?? null,
                            'description' => $item['description'] ?? '',
                            'products'    => $productsData,
                        ];
                    })
                    ->toArray();
            });

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('SmartCalendar Error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}

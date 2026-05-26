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
            // Sử dụng cache_v4 để cập nhật dữ liệu ngày lễ AI và sản phẩm mới
            $data = Cache::remember('smart_calendar_v4', 43200, function () use ($aiService) {
                // 1. Đề cử danh sách ngày lễ lịch âm gần nhất từ AI
                $aiHolidays = $aiService->getUpcomingLunarHolidays(3);

                if (empty($aiHolidays)) {
                    return [];
                }

                // 2. Lấy danh sách chi tiết các sản phẩm được AI đề xuất cho các ngày lễ
                $allProductIds = collect($aiHolidays)->flatMap(fn($h) => $h['product_ids'] ?? [])->unique()->toArray();
                
                $dbProducts = Product::active()
                    ->with(['images', 'category'])
                    ->whereIn('id', $allProductIds)
                    ->get()
                    ->keyBy('id');

                // 3. Kết xuất thông tin ngày lễ động kết hợp với sản phẩm liên quan tương ứng
                return collect($aiHolidays)
                    ->map(function ($item) use ($dbProducts) {
                        $holidayProductIds = $item['product_ids'] ?? [];
                        
                        $holidayProducts = [];
                        foreach ($holidayProductIds as $id) {
                            if (isset($dbProducts[$id])) {
                                $p = $dbProducts[$id];
                                $holidayProducts[] = [
                                    'id'    => $p->id,
                                    'name'  => $p->name,
                                    'slug'  => $p->slug,
                                    'price' => (int) $p->price,
                                    'image' => $p->primary_image_url,
                                ];
                            }
                        }

                        // Fallback nếu AI không tìm thấy sản phẩm phù hợp hoặc sản phẩm đó không hoạt động
                        if (empty($holidayProducts)) {
                            $fallbackProducts = Product::active()
                                ->with(['images', 'category'])
                                ->orderBy('created_at', 'desc')
                                ->limit(4)
                                ->get();
                            $holidayProducts = $fallbackProducts->map(fn($p) => [
                                'id'    => $p->id,
                                'name'  => $p->name,
                                'slug'  => $p->slug,
                                'price' => (int) $p->price,
                                'image' => $p->primary_image_url,
                            ])->values()->toArray();
                        }

                        return [
                            'id'          => null,
                            'name'        => $item['name'] ?? '',
                            'lunarLabel'  => $item['lunarLabel'] ?? '',
                            'nextDate'    => $item['nextDate'] ?? null,
                            'description' => $item['description'] ?? '',
                            'products'    => $holidayProducts,
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

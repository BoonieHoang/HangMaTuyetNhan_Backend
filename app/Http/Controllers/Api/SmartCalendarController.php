<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmartCalendarController extends Controller
{
    public function index(OpenAIService $aiService)
    {
        try {
            $data = Cache::remember('smart_calendar_v2', 43200, function () use ($aiService) {
                // 1. Get all holidays from DB with up to 3 active products each
                $holidays = Holiday::with([
                    'products' => fn($q) => $q->active()
                        ->with(['primaryImage', 'category'])
                        ->orderBy('views', 'desc')
                        ->limit(3),
                ])->get();

                if ($holidays->isEmpty()) {
                    return [];
                }

                // 2. Ask Gemini to enrich: next occurrence date + lunar label + description
                $enriched = $aiService->enrichHolidays($holidays->toArray());

                if (empty($enriched)) {
                    return [];
                }

                // 3. Merge AI-generated metadata with DB products
                $holidayMap = $holidays->keyBy('id');

                return collect($enriched)
                    ->map(function ($item) use ($holidayMap) {
                        $holiday = $holidayMap[$item['id']] ?? null;
                        if (!$holiday) return null;

                        return [
                            'id'          => $holiday->id,
                            'name'        => $holiday->name,
                            'lunarLabel'  => $item['lunarLabel']  ?? '',
                            'nextDate'    => $item['nextDate']    ?? null,
                            'description' => $item['description'] ?? '',
                            'products'    => $holiday->products->map(fn($p) => [
                                'id'    => $p->id,
                                'name'  => $p->name,
                                'slug'  => $p->slug,
                                'price' => (int) $p->price,
                                'image' => $p->primary_image_url,
                            ])->values()->toArray(),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->toArray();
            });

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('SmartCalendar Error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}

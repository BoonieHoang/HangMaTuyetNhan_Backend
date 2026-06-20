<?php

namespace App\Services;

use App\Models\Product;
use App\Services\LunarCalendarService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;
    protected $apiKey;
    protected $model;
    protected LunarCalendarService $lunarService;

    public function __construct()
    {
        $this->apiKey       = config('services.gemini.api_key') ?: config('services.openai.api_key');
        $this->model        = config('services.gemini.model', 'gemini-2.5-flash');
        $this->lunarService = new LunarCalendarService();
        $this->client       = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'headers'  => ['Content-Type' => 'application/json'],
        ]);
    }

    /**
     * Load compact product catalog (cached 60 min).
     */
    protected function getProductCatalog(): array
    {
        return Cache::remember('chatbot_product_catalog', 3600, function () {
            return Product::active()
                ->with(['category', 'holidays'])
                ->get()
                ->map(fn($p) => [
                    'id'       => $p->id,
                    'name'     => $p->name,
                    'slug'     => $p->slug,
                    'price'    => (int) $p->price,
                    'category' => $p->category?->name ?? '',
                    'holidays' => $p->holidays->pluck('name')->toArray(),

                    'image'    => $p->primary_image_url,
                ])
                ->toArray();
        });
    }

    /**
     * Build compact catalog lines for system prompt.
     */
    protected function buildCatalogLines(array $catalog): string
    {
        $lines = [];
        foreach ($catalog as $p) {
            $dip  = implode(', ', $p['holidays']) ?: 'Đa dụng';
            $lines[] = "[{$p['id']}] {$p['name']} | {$p['price']}đ | {$p['category']} | Dịp: {$dip}";
        }
        return implode("\n", $lines);
    }

    /**
     * Pick up to 4 products from catalog by ID.
     */
    protected function enrichProducts(array $ids, array $catalog): array
    {
        if (empty($ids)) return [];
        $map = array_column($catalog, null, 'id');
        $result = [];
        foreach (array_slice($ids, 0, 4) as $id) {
            if (isset($map[$id])) $result[] = $map[$id];
        }
        return $result;
    }

    public function chat(array $history, string $userMessage): array
    {
        // Tính ngày âm lịch chính xác theo GMT+7 bằng thư viện chuyên dụng
        $todayStr = $this->lunarService->todayPromptString();

        $catalog      = $this->getProductCatalog();
        $catalogLines = $this->buildCatalogLines($catalog);

        $systemPrompt = <<<PROMPT
Bạn là "Trợ lý Smart Calendar" của cửa hàng đồ lễ Tuyết Nhàn — chuyên tư vấn sản phẩm mã vàng, đồ lễ phẩm và nghi lễ thờ cúng truyền thống Việt Nam.

=== NGÀY HIỆN TẠI (ĐÃ ĐƯỢC TÍNH CHÍNH XÁC BỞI HỆ THỐNG) ===
Hôm nay là {$todayStr}.
ĐÂY LÀ THÔNG TIN CHÍNH XÁC. TUYỆT ĐỐI KHÔNG tự tính lại ngày âm lịch. Hãy sử dụng đúng ngày âm lịch này khi trả lời.

=== DANH MỤC SẢN PHẨM ===
Format: [ID] Tên | Giá | Danh mục | Dịp phù hợp
{$catalogLines}

=== NHIỆM VỤ ===
1. Trả lời về ngày lễ âm lịch, ý nghĩa nghi lễ, lễ vật, và tư vấn sản phẩm.
2. Khi người dùng hỏi "hôm nay là ngày mấy âm" hoặc "ngày âm hôm nay": trả lời đúng theo ngày âm lịch đã cung cấp ở trên.
3. Khi hỏi về dịp lễ sắp tới: dựa vào ngày âm lịch hôm nay để xác định ngày lễ nào ĐÃ QUA và ngày lễ nào SẮP TỚI. KHÔNG báo người dùng rằng ngày lễ hôm nay là "ngày mai".
4. Khi hỏi về dịp lễ (Rằm, Mùng Một, Tết Đoan Ngọ, Tháng Cô Hồn...): giải thích ý nghĩa, gợi ý lễ vật, chọn 2-4 sản phẩm phù hợp từ danh mục.
5. Khi hỏi mua sắm: gợi ý sản phẩm liên quan từ danh mục.
6. Từ chối lịch sự nếu ngoài chủ đề tâm linh / thờ cúng.

=== ĐỊNH DẠNG PHẢN HỒI ===
Luôn trả về JSON hợp lệ, KHÔNG thêm văn bản ngoài JSON:
{"text": "Nội dung trả lời (Markdown được phép)", "product_ids": []}
Nếu có sản phẩm phù hợp: điền ID vào product_ids (tối đa 4). Không liên quan: để [].
PROMPT;

        // Build Gemini conversation history
        $contents = [];
        foreach ($history as $msg) {
            $role       = ($msg['role'] === 'assistant' || $msg['role'] === 'model') ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        try {
            if (empty($this->apiKey)) {
                throw new \Exception('Gemini API key is not configured.');
            }

            $url      = "models/{$this->model}:generateContent?key=" . $this->apiKey;
            $response = $this->client->post($url, [
                'json' => [
                    'contents'          => $contents,
                    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                    'generationConfig'  => [
                        'responseMimeType' => 'application/json',
                        'responseSchema'   => [
                            'type'       => 'OBJECT',
                            'properties' => [
                                'text'        => ['type' => 'STRING'],
                                'product_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']],
                            ],
                            'required' => ['text', 'product_ids'],
                        ],
                    ],
                ],
            ]);

            $result     = json_decode($response->getBody()->getContents(), true);
            $rawText    = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $tokensUsed = $result['usageMetadata']['totalTokenCount'] ?? 0;

            $parsed = json_decode($rawText, true);

            // Fallback if JSON parse fails
            if (!is_array($parsed) || !isset($parsed['text'])) {
                return ['response' => $rawText, 'suggested_products' => [], 'tokens_used' => $tokensUsed];
            }

            $ids               = array_map('intval', array_values(array_filter($parsed['product_ids'] ?? [], 'is_numeric')));
            $suggestedProducts = $this->enrichProducts($ids, $catalog);

            return [
                'response'           => $parsed['text'],
                'suggested_products' => $suggestedProducts,
                'tokens_used'        => $tokensUsed,
            ];
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            $errMsg = config('app.debug')
                ? 'Lỗi kết nối Gemini: ' . $e->getMessage()
                : 'Xin lỗi, tôi đang gặp sự cố kết nối. Vui lòng thử lại sau.';
            return [
                'response'           => $errMsg,
                'suggested_products' => [],
                'tokens_used'        => 0,
            ];
        }
    }

    /**
     * Enrich a list of DB holidays with AI-generated:
     *   - nextDate      : next upcoming Gregorian date (YYYY-MM-DD)
     *   - lunarLabel    : short lunar label (e.g. "15/07 Âm lịch")
     *   - description   : 1-2 sentence description + ritual tips
     */
    public function enrichHolidays(array $holidays): array
    {
        if (empty($holidays) || empty($this->apiKey)) return [];

        // Tính ngày âm lịch hôm nay bằng thư viện chuyên dụng (GMT+7 chính xác)
        $todayStr    = $this->lunarService->todayPromptString();
        $tomorrowSolar = \Carbon\Carbon::tomorrow('Asia/Ho_Chi_Minh')->format('d/m/Y');
        $list        = collect($holidays)->map(fn($h) => ['id' => $h['id'], 'name' => $h['name']])->toJson(JSON_UNESCAPED_UNICODE);

        $prompt = "Hệ thống đã tính chính xác: {$todayStr}. Ngày mai dương lịch là {$tomorrowSolar}.\n"
                . "Đây là danh sách các ngày lễ truyền thống từ cơ sở dữ liệu của cửa hàng đồ lễ Tuyết Nhàn:\n{$list}\n\n"
                . "TUYỆT ĐỐI KHÔNG tự tính lại ngày âm lịch hôm nay. Hãy dùng đúng thông tin trên.\n"
                . "Với MỖI ngày lễ trên, hãy trả về:\n"
                . "1. nextDate: ngày dương lịch tiếp theo của ngày lễ đó (từ ngày mai {$tomorrowSolar} trở đi), format YYYY-MM-DD.\n"
                . "2. lunarLabel: nhãn âm lịch ngắn gọn, ví dụ '15/07 Âm lịch' hoặc '01/01 Âm lịch'.\n"
                . "3. description: 1-2 câu mô tả ý nghĩa ngày lễ và gợi ý cần chuẩn bị gì.\n"
                . "Lưu ý: Mùng Một và Rằm xảy ra hàng tháng — hãy tính tháng âm lịch sắp tới gần nhất tính từ ngày mai.";

        try {
            $url      = "models/{$this->model}:generateContent?key=" . $this->apiKey;
            $response = $this->client->post($url, [
                'json' => [
                    'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema'   => [
                            'type'       => 'OBJECT',
                            'properties' => [
                                'holidays' => [
                                    'type'  => 'ARRAY',
                                    'items' => [
                                        'type'       => 'OBJECT',
                                        'properties' => [
                                            'id'          => ['type' => 'INTEGER'],
                                            'nextDate'    => ['type' => 'STRING'],
                                            'lunarLabel'  => ['type' => 'STRING'],
                                            'description' => ['type' => 'STRING'],
                                        ],
                                        'required' => ['id', 'nextDate', 'lunarLabel', 'description'],
                                    ],
                                ],
                            ],
                            'required' => ['holidays'],
                        ],
                    ],
                ],
            ]);

            $result  = json_decode($response->getBody()->getContents(), true);
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $parsed  = json_decode($rawText, true);

            return $parsed['holidays'] ?? [];
        } catch (\Exception $e) {
            Log::error('Gemini enrichHolidays Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Propose upcoming Vietnamese traditional lunar holidays using AI.
     * Returns an array of holidays, each with:
     *   - name
     *   - nextDate (Gregorian YYYY-MM-DD)
     *   - lunarLabel (e.g. "15/07 Âm lịch")
     *   - description
     */
    public function getUpcomingLunarHolidays(int $limit = 4): array
    {
        if (empty($this->apiKey)) return [];

        // Tính ngày âm lịch hôm nay chính xác theo GMT+7
        $todayStr      = $this->lunarService->todayPromptString();
        $todaySolar    = \Carbon\Carbon::now('Asia/Ho_Chi_Minh')->format('d/m/Y');
        
        $start = \Carbon\Carbon::now('Asia/Ho_Chi_Minh')->startOfDay();
        $upcomingMilestones = [];
        
        $monthNames = [
            1 => 'Giêng', 2 => 'Hai', 3 => 'Ba', 4 => 'Tư', 5 => 'Năm', 6 => 'Sáu',
            7 => 'Bảy', 8 => 'Tám', 9 => 'Chín', 10 => 'Mười', 11 => 'Mười Một', 12 => 'Chạp'
        ];

        // Duyệt qua tối đa 120 ngày để tìm các mốc Mùng Một và Rằm tiếp theo (không dùng dữ liệu ngày lễ hardcode)
        for ($i = 0; $i < 400; $i++) {
            $check = $start->copy()->addDays($i);
            $lunar = $this->lunarService->gregorianToLunar($check);
            
            $name = null;
            if ($lunar['day'] === 1) {
                $name = "Mùng Một tháng " . ($monthNames[$lunar['month']] ?? $lunar['month']);
            } elseif ($lunar['day'] === 15) {
                $name = "Rằm tháng " . ($monthNames[$lunar['month']] ?? $lunar['month']);
            }
            
            if ($name !== null) {
                $upcomingMilestones[] = [
                    'name'       => $name,
                    'lunarLabel' => sprintf('%02d/%02d Âm lịch', $lunar['day'], $lunar['month']),
                    'solarDate'  => $check->format('d/m/Y'),
                    'gregorian'  => $check->format('Y-m-d'),
                ];
                if (count($upcomingMilestones) >= 4) {
                    break;
                }
            }
        }

        $milestoneLines = [];
        foreach ($upcomingMilestones as $idx => $m) {
            $seq = $idx + 1;
            $milestoneLines[] = "{$seq}. {$m['name']} | Ngày âm: {$m['lunarLabel']} | Ngày dương tương ứng: {$m['solarDate']} ({$m['gregorian']})";
        }
        $milestoneText = implode("\n", $milestoneLines);

        $catalog      = $this->getProductCatalog();
        $catalogLines = $this->buildCatalogLines($catalog);

        $prompt = "Hệ thống đã tính chính xác: {$todayStr} (múi giờ GMT+7, Hà Nội).\n"
                . "Ngày hôm nay dương lịch là {$todaySolar}.\n"
                . "TUYỆT ĐỐI KHÔNG tự tính lại ngày âm lịch hôm nay. Hãy dùng đúng thông tin trên.\n\n"
                . "Đây là danh mục sản phẩm của cửa hàng đồ lễ Tuyết Nhàn:\n"
                . "{$catalogLines}\n\n"
                . "Dưới đây là danh sách các mốc thời gian Mùng Một và Rằm âm lịch tiếp theo đã được hệ thống tính toán chính xác dương lịch:\n"
                . "{$milestoneText}\n\n"
                . "Hãy đề cử danh sách gồm đúng {$limit} ngày lễ truyền thống Việt Nam theo lịch âm sắp tới gần nhất để hiển thị trên Smart Calendar, bắt đầu từ HÔM NAY trở đi.\n"
                . "Lưu ý:\n"
                . "- Nếu HÔM NAY chính là ngày diễn ra một ngày lễ lớn hoặc mốc cúng quan trọng (ví dụ mùng 5 tháng 5 Âm lịch là Tết Đoan Ngọ, hoặc ngày Rằm, Mùng Một...), hãy đưa ngày lễ đó vào danh sách đầu tiên với `nextDate` chính là ngày hôm nay. Đừng bỏ qua nó.\n"
                . "- Hãy sử dụng chính xác các ngày dương lịch tương ứng của ngày Mùng Một và Rằm từ danh sách mốc thời gian hệ thống cung cấp ở trên để trả về thông tin.\n\n"
                . "Với mỗi ngày lễ, hãy trả về:\n"
                . "1. name: Tên ngày lễ (ví dụ: 'Tết Đoan Ngọ', 'Rằm tháng Năm', 'Mùng Một tháng Sáu').\n"
                . "2. nextDate: ngày dương lịch tương ứng của ngày lễ đó, định dạng YYYY-MM-DD.\n"
                . "3. lunarLabel: nhãn ngày âm lịch tương ứng, ví dụ '15/05 Âm lịch', '01/06 Âm lịch'.\n"
                . "4. description: 1-2 câu mô tả ngắn gọn ý nghĩa ngày lễ và gợi ý lễ vật cần chuẩn bị.\n"
                . "5. product_ids: mảng chứa từ 2 đến 4 ID sản phẩm phù hợp nhất với ngày lễ này từ danh mục sản phẩm trên.";

        try {
            $url      = "models/{$this->model}:generateContent?key=" . $this->apiKey;
            $response = $this->client->post($url, [
                'json' => [
                    'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema'   => [
                            'type'       => 'OBJECT',
                            'properties' => [
                                'holidays' => [
                                    'type'  => 'ARRAY',
                                    'items' => [
                                        'type'       => 'OBJECT',
                                        'properties' => [
                                            'name'        => ['type' => 'STRING'],
                                            'nextDate'    => ['type' => 'STRING'],
                                            'lunarLabel'  => ['type' => 'STRING'],
                                            'description' => ['type' => 'STRING'],
                                            'product_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']],
                                        ],
                                        'required' => ['name', 'nextDate', 'lunarLabel', 'description', 'product_ids'],
                                    ],
                                ],
                            ],
                            'required' => ['holidays'],
                        ],
                    ],
                ],
            ]);

            $result  = json_decode($response->getBody()->getContents(), true);
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $parsed  = json_decode($rawText, true);

            return $parsed['holidays'] ?? [];
        } catch (\Exception $e) {
            Log::error('Gemini getUpcomingLunarHolidays Error: ' . $e->getMessage());
            return [];
        }
    }
}


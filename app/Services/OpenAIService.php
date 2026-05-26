<?php

namespace App\Services;

use App\Models\Product;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY') ?: env('OPENAI_API_KEY');
        $this->model  = env('GEMINI_MODEL', 'gemini-flash-latest');
        $this->client = new Client([
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
                ->with(['category', 'holidays', 'purposes'])
                ->get()
                ->map(fn($p) => [
                    'id'       => $p->id,
                    'name'     => $p->name,
                    'slug'     => $p->slug,
                    'price'    => (int) $p->price,
                    'category' => $p->category?->name ?? '',
                    'holidays' => $p->holidays->pluck('name')->toArray(),
                    'purposes' => $p->purposes->pluck('name')->toArray(),
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
            $muc  = $p['purposes'] ? ' | Mục đích: ' . implode(', ', $p['purposes']) : '';
            $lines[] = "[{$p['id']}] {$p['name']} | {$p['price']}đ | {$p['category']} | Dịp: {$dip}{$muc}";
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
        $now  = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $days = [0 => 'Chủ Nhật', 1 => 'Thứ Hai', 2 => 'Thứ Ba', 3 => 'Thứ Tư', 4 => 'Thứ Năm', 5 => 'Thứ Sáu', 6 => 'Thứ Bảy'];
        $dateStr = $days[$now->dayOfWeek] . ', ngày ' . $now->format('d/m/Y');

        $catalog      = $this->getProductCatalog();
        $catalogLines = $this->buildCatalogLines($catalog);

        $systemPrompt = <<<PROMPT
Bạn là "Trợ lý Smart Calendar" của cửa hàng đồ lễ Tuyết Nhàn — chuyên tư vấn sản phẩm mã vàng, đồ lễ phẩm và nghi lễ thờ cúng truyền thống Việt Nam.

=== NGÀY HIỆN TẠI ===
Hôm nay là {$dateStr} (dương lịch). Hãy tự tính ngày âm lịch tương ứng chính xác.

=== DANH MỤC SẢN PHẨM ===
Format: [ID] Tên | Giá | Danh mục | Dịp phù hợp
{$catalogLines}

=== NHIỆM VỤ ===
1. Trả lời về ngày lễ âm lịch, ý nghĩa nghi lễ, lễ vật, và tư vấn sản phẩm.
2. Khi hỏi về dịp lễ (Rằm, Mùng Một, Tết Thanh Minh, Tháng Cô Hồn...): giải thích ý nghĩa, gợi ý lễ vật, chọn 2-4 sản phẩm phù hợp từ danh mục.
3. Khi hỏi mua sắm: gợi ý sản phẩm liên quan từ danh mục.
4. Từ chối lịch sự nếu ngoài chủ đề tâm linh / thờ cúng.

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

        $now      = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $dateStr  = $now->format('d/m/Y');
        $list     = collect($holidays)->map(fn($h) => ['id' => $h['id'], 'name' => $h['name']])->toJson(JSON_UNESCAPED_UNICODE);

        $prompt = "Hôm nay là {$dateStr} (dương lịch). Đây là danh sách các ngày lễ truyền thống từ cơ sở dữ liệu của cửa hàng đồ lễ Tuyết Nhàn:\n{$list}\n\n"
                . "Với MỖI ngày lễ trên, hãy tính toán chính xác và trả về:\n"
                . "1. nextDate: ngày dương lịch gần nhất sắp đến (từ ngày mai trở đi), format YYYY-MM-DD.\n"
                . "2. lunarLabel: nhãn âm lịch ngắn gọn, ví dụ '15/07 Âm lịch' hoặc '01/01 Âm lịch'.\n"
                . "3. description: 1-2 câu mô tả ý nghĩa ngày lễ và gợi ý cần chuẩn bị gì.\n"
                . "Lưu ý: Mùng Một và Rằm xảy ra hàng tháng — hãy tính tháng âm lịch sắp tới gần nhất.";

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
    public function getUpcomingLunarHolidays(int $limit = 3): array
    {
        if (empty($this->apiKey)) return [];

        $now     = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $dateStr = $now->format('d/m/Y');

        $prompt = "Hôm nay là ngày dương lịch: {$dateStr}.\n"
                . "Hãy đề cử danh sách gồm đúng {$limit} ngày lễ truyền thống Việt Nam theo lịch âm sắp tới gần nhất (bắt đầu từ ngày hôm nay trở đi).\n"
                . "Lưu ý bao gồm cả ngày Rằm hoặc Mùng Một âm lịch sắp tới gần nhất nếu nó là ngày cúng lễ quan trọng tiếp theo.\n"
                . "Với mỗi ngày lễ, hãy trả về:\n"
                . "1. name: Tên ngày lễ (ví dụ: 'Tết Đoan Ngọ', 'Rằm tháng Bảy (cúng Cô Hồn)', 'Mùng Một hàng tháng').\n"
                . "2. nextDate: ngày dương lịch tiếp theo tương ứng của ngày lễ đó, định dạng YYYY-MM-DD.\n"
                . "3. lunarLabel: nhãn ngày âm lịch tương ứng, ví dụ '05/05 Âm lịch', '15/07 Âm lịch'.\n"
                . "4. description: 1-2 câu mô tả ngắn gọn ý nghĩa ngày lễ và gợi ý lễ vật cần chuẩn bị.";

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
                                        ],
                                        'required' => ['name', 'nextDate', 'lunarLabel', 'description'],
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


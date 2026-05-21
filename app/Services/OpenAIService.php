<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        // Support GEMINI_API_KEY first, fallback to OPENAI_API_KEY in case of variable reuse on Railway
        $this->apiKey = env('GEMINI_API_KEY') ?: env('OPENAI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'gemini-flash-latest');
        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function chat(array $history, string $userMessage): array
    {
        $now = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $days = [
            0 => 'Chủ Nhật',
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
        ];
        $dayOfWeek = $days[$now->dayOfWeek];
        $dateStr = $dayOfWeek . ", ngày " . $now->format('d/m/Y');

        $systemPrompt = "Bạn là trợ lý tư vấn sản phẩm mã vàng và đồ lễ phẩm. Chỉ trả lời các câu hỏi liên quan đến sản phẩm, lễ nghi, phong tục thờ cúng của người Việt Nam. Từ chối lịch sự nếu hỏi ngoài chủ đề. Hôm nay là " . $dateStr . " (Dương lịch). Hãy luôn sử dụng thông tin ngày hiện tại này để trả lời chuẩn xác nếu khách hỏi về thời gian, ngày tháng hoặc lễ tiết.";

        // Translate history to Gemini format (roles: user, model)
        $contents = [];
        foreach ($history as $msg) {
            // Translate assistant to model
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'model') ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $msg['content']]
                ]
            ];
        }

        // Add latest user message
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $userMessage]
            ]
        ];

        try {
            if (empty($this->apiKey)) {
                throw new \Exception("Gemini API key is not configured.");
            }

            $url = "models/{$this->model}:generateContent?key=" . $this->apiKey;

            $response = $this->client->post($url, [
                'json' => [
                    'contents' => $contents,
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemPrompt]
                        ]
                    ]
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            $responseText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $tokensUsed = $result['usageMetadata']['totalTokenCount'] ?? 0;

            return [
                'response' => $responseText,
                'tokens_used' => $tokensUsed,
            ];
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return [
                'response' => 'Xin lỗi, tôi đang gặp sự cố kết nối. Vui lòng thử lại sau.',
                'tokens_used' => 0,
            ];
        }
    }
}

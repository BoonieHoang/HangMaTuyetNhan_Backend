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
        $this->apiKey = config('openai.api_key');
        $this->model = config('openai.model');
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function chat(array $history, string $userMessage): array
    {
        $systemPrompt = "Bạn là trợ lý tư vấn sản phẩm mã vàng và đồ lễ phẩm. Chỉ trả lời các câu hỏi liên quan đến sản phẩm, lễ nghi, phong tục thờ cúng của người Việt Nam. Từ chối lịch sự nếu hỏi ngoài chủ đề.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'max_tokens' => config('openai.max_tokens', 500),
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return [
                'response' => $result['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $result['usage']['total_tokens'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return [
                'response' => 'Xin lỗi, tôi đang gặp sự cố kết nối. Vui lòng thử lại sau.',
                'tokens_used' => 0,
            ];
        }
    }
}

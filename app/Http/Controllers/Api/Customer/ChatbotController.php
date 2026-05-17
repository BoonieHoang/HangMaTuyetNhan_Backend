<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\ChatbotRequest;
use App\Models\ChatbotLog;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    protected $openAiService;

    public function __construct(OpenAIService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    public function chat(ChatbotRequest $request)
    {
        $history = $request->input('history', []);
        $userMessage = $request->input('message');

        $result = $this->openAiService->chat($history, $userMessage);

        $userId = auth('sanctum')->check() ? auth('sanctum')->id() : null;
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();

        ChatbotLog::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'message' => $userMessage,
            'response' => $result['response'],
            'tokens_used' => $result['tokens_used'],
            'created_at' => now(),
        ]);

        return response()->json([
            'response' => $result['response'],
            'tokens_used' => $result['tokens_used'],
        ]);
    }
}

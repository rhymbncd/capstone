<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = $request->input('message');

        $systemInstruction = '
            You are a Math Tutor.
            Answer only math-related questions.
            Use LaTeX formatting for equations.
        ';

        try {

            $apiKey = config('services.openrouter.key');

            if (! $apiKey) {
                return response()->json([
                    'status' => 'error',
                    'reply' => 'Missing API key.',
                ], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => url('/'),
                'X-Title' => 'Math Tutor AI',
            ])->post('https://openrouter.ai/api/v1/chat/completions', [

                'model' => 'openai/gpt-3.5-turbo',

                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemInstruction,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage,
                    ],
                ],

                'temperature' => 0.2,
                'max_tokens' => 500,

            ]);

            if ($response->successful()) {

                $data = $response->json();

                $reply = $data['choices'][0]['message']['content']
                    ?? 'No AI response.';

                return response()->json([
                    'status' => 'success',
                    'reply' => $reply,
                ]);
            }

            Log::error('OpenRouter Error: '.$response->body());

            return response()->json([
                'status' => 'error',
                'reply' => 'AI service failed.',
            ], 500);

        } catch (\Exception $e) {

            Log::error('Chatbot Exception: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'reply' => 'Server error.',
            ], 500);
        }
    }
}

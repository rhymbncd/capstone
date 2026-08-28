<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuizCustomTopic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Teacher-defined extra topics for the quiz generator. Shared pool across
 * all approved teachers; authorisation is the role:teacher route middleware.
 */
class CustomTopicController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'customTopics' => QuizCustomTopic::orderBy('created_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module_key' => ['nullable', 'string', 'max:50'],
            'topic_key' => ['required', 'string', 'max:255'],
            'topic_name' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $topic = QuizCustomTopic::create($validated);

        return response()->json(['customTopic' => $topic], 201);
    }

    public function destroy(QuizCustomTopic $customTopic): JsonResponse
    {
        $customTopic->delete();

        return response()->json(['deleted' => true]);
    }
}

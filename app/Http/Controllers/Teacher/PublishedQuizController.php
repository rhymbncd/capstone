<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuizPublished;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The published-quiz library. Every approved teacher shares one pool —
 * there is no per-teacher ownership column — so authorisation here is
 * simply the role:teacher route middleware.
 */
class PublishedQuizController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['published' => QuizPublished::orderByDesc('published_at')->get()]);
    }

    /**
     * Publish (or replace) the quiz for a topic. Upserts on topic_key.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic_key' => ['required', 'string', 'max:255'],
            'pretest' => ['required', 'json'],
            'posttest' => ['required', 'json'],
            'activity' => ['required', 'json'],
        ]);

        $row = QuizPublished::updateOrCreate(
            ['topic_key' => $validated['topic_key']],
            [
                'pretest' => $validated['pretest'],
                'posttest' => $validated['posttest'],
                'activity' => $validated['activity'],
                'published_at' => now(),
            ],
        );

        return response()->json(['published' => $row]);
    }

    public function destroy(string $topicKey): JsonResponse
    {
        QuizPublished::where('topic_key', $topicKey)->delete();

        return response()->json(['deleted' => true]);
    }
}

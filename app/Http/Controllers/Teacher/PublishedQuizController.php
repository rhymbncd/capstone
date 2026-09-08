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
     *
     * When this replaces an existing quiz with different pre/post questions,
     * QuizPublishedObserver clears every student's recorded attempts for the
     * topic so nobody keeps a score against questions that are now gone.
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

    /**
     * Unpublish the quiz for a topic. Deleted one model at a time (rather
     * than a mass query delete) so QuizPublishedObserver fires and resets
     * every student's pre/post progress for that topic.
     */
    public function destroy(string $topicKey): JsonResponse
    {
        QuizPublished::where('topic_key', $topicKey)->get()->each->delete();

        return response()->json(['deleted' => true]);
    }
}

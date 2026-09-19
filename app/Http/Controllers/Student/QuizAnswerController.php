<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentQuizAnswer;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class QuizAnswerController extends Controller
{
    /**
     * Every saved pre-test/post-test/activity answer attempt for the
     * authenticated student — lets the student review exactly what they
     * answered on an attempt they've already submitted, since the store
     * endpoint below won't let them resubmit it.
     */
    public function index(): JsonResponse
    {
        $attempts = StudentQuizAnswer::where('session_id', (string) Auth::id())
            ->get(['topic_key', 'phase', 'answers', 'score', 'total', 'created_at']);

        return response()->json(['attempts' => $attempts]);
    }

    /**
     * Save the answer list a student gave for one pre-test / post-test /
     * activity attempt. Ownership is the session, never the request body.
     *
     * Each of these is a one-time attempt: once a row exists for this
     * (session_id, topic_key, phase), it has already been submitted and
     * this returns 409 instead of overwriting it, so a student can't
     * retake by resubmitting straight to this endpoint even if the
     * quiz UI itself is bypassed.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic_key' => ['required', 'string', 'max:255'],
            'phase' => ['required', Rule::in(['pre', 'post', 'activity'])],
            'answers' => ['required', 'array'],
            'score' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $sessionId = (string) $user->id;

        $alreadySubmitted = StudentQuizAnswer::where('session_id', $sessionId)
            ->where('topic_key', $validated['topic_key'])
            ->where('phase', $validated['phase'])
            ->exists();

        if ($alreadySubmitted) {
            return response()->json([
                'saved' => false,
                'already_submitted' => true,
                'message' => 'You have already submitted this attempt.',
            ], 409);
        }

        try {
            StudentQuizAnswer::create([
                'session_id' => $sessionId,
                'topic_key' => $validated['topic_key'],
                'phase' => $validated['phase'],
                'student_name' => $user->name,
                'answers' => $validated['answers'],
                'score' => $validated['score'],
                'total' => $validated['total'],
            ]);
        } catch (QueryException $e) {
            if (! str_contains(strtolower($e->getMessage()), 'unique')) {
                throw $e;
            }

            // Lost a race against a duplicate request for the same attempt.
            return response()->json([
                'saved' => false,
                'already_submitted' => true,
                'message' => 'You have already submitted this attempt.',
            ], 409);
        }

        return response()->json(['saved' => true]);
    }
}

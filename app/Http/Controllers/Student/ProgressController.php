<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizPublished;
use App\Models\StudentProgress;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProgressController extends Controller
{
    /**
     * Every visible student_progress row for the authenticated student —
     * the module page's completed-topics / reading-progress rehydration and
     * the dashboard's analytics both read from this.
     *
     * Pre/post attempts for a topic whose teacher-published quiz no longer
     * exists are withheld, so unpublishing a quiz immediately drops the
     * topic's progress from the student's view even if the row has not yet
     * been pruned. Reading progress and the dashboard's own summative
     * attempts are always returned.
     */
    public function index(): JsonResponse
    {
        $publishedTopicKeys = QuizPublished::query()->pluck('topic_key');

        $rows = StudentProgress::where('session_id', (string) Auth::id())
            ->get(['topic_key', 'phase', 'score', 'total', 'passed', 'created_at'])
            ->filter(fn (StudentProgress $row): bool => ! in_array($row->phase, StudentProgress::QUIZ_PHASES, true)
                || in_array($row->topic_key, StudentProgress::SELF_DIRECTED_TOPIC_KEYS, true)
                || $publishedTopicKeys->contains($row->topic_key))
            ->values();

        return response()->json(['progress' => $rows]);
    }

    /**
     * Save a reading-progress row, or a one-time pre-test/post-test/summative
     * attempt, for the authenticated student. session_id and student_name
     * come from the session, never the request, so a student can only ever
     * write their own.
     *
     * Reading progress (phase "reading") tracks module PDF scroll position
     * and is freely upserted as the student reads further. Every other
     * phase is a graded attempt and is one-time only: once a row exists for
     * this (session_id, topic_key, phase), the attempt is already submitted
     * and this returns 409 instead of overwriting it — enforced here, not
     * just by hiding the retake button client-side, so a student can't
     * bypass it by calling this endpoint directly.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic_key' => ['required', 'string', 'max:255'],
            'phase' => ['required', Rule::in(['pre', 'post', 'reading'])],
            'score' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:0'],
            'passed' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $sessionId = (string) $user->id;

        if ($validated['phase'] === 'reading') {
            StudentProgress::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'topic_key' => $validated['topic_key'],
                    'phase' => 'reading',
                ],
                [
                    'student_name' => $user->name,
                    'score' => $validated['score'],
                    'total' => $validated['total'],
                    'created_at' => now(),
                ],
            );

            return response()->json(['saved' => true]);
        }

        $alreadySubmitted = StudentProgress::where('session_id', $sessionId)
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
            StudentProgress::create([
                'session_id' => $sessionId,
                'topic_key' => $validated['topic_key'],
                'phase' => $validated['phase'],
                'student_name' => $user->name,
                'score' => $validated['score'],
                'total' => $validated['total'],
                'passed' => $validated['passed'] ?? false,
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

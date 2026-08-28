<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProgressController extends Controller
{
    /**
     * Every student_progress row for the authenticated student — the module
     * page's completed-topics / reading-progress rehydration and the
     * dashboard's analytics both read from this.
     */
    public function index(): JsonResponse
    {
        $rows = StudentProgress::where('session_id', (string) Auth::id())
            ->get(['topic_key', 'phase', 'score', 'total', 'passed', 'created_at']);

        return response()->json(['progress' => $rows]);
    }

    /**
     * Upsert one attempt (pre/post) or reading-progress row for the
     * authenticated student. session_id and student_name come from the
     * session, never the request, so a student can only ever write their own.
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

        StudentProgress::updateOrCreate(
            [
                'session_id' => (string) $user->id,
                'topic_key' => $validated['topic_key'],
                'phase' => $validated['phase'],
            ],
            [
                'student_name' => $user->name,
                'score' => $validated['score'],
                'total' => $validated['total'],
                'passed' => $validated['passed'] ?? false,
                'created_at' => now(),
            ],
        );

        return response()->json(['saved' => true]);
    }
}

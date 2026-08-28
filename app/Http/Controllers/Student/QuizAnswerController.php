<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentQuizAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuizAnswerController extends Controller
{
    /**
     * Upsert the answer list a student gave for one pre-test / post-test /
     * activity attempt. Ownership is the session, never the request body.
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

        StudentQuizAnswer::updateOrCreate(
            [
                'session_id' => (string) $user->id,
                'topic_key' => $validated['topic_key'],
                'phase' => $validated['phase'],
            ],
            [
                'student_name' => $user->name,
                'answers' => $validated['answers'],
                'score' => $validated['score'],
                'total' => $validated['total'],
            ],
        );

        return response()->json(['saved' => true]);
    }
}

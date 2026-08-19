<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\StudentQuizAnswer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StudentAnswersController extends Controller
{
    /**
     * Topic keys to their human-readable names, matching the topic names
     * used on the student's Modules page (resources/views/dashboard/module.blade.php).
     *
     * @var array<string, string>
     */
    private const TOPIC_NAMES = [
        'ari' => 'Arithmetic Sequence',
        'geo' => 'Geometric Sequence',
        'har' => 'Harmonic Sequence',
        'fib' => 'Fibonacci Sequence',
        'fin' => 'Finite and Infinite Sequence',
        'div' => 'Division of Polynomials',
        'rem' => 'Remainder & Factor Theorem',
        'poly' => 'Polynomial Equations',
        'rat' => 'Rational Equations',
        'rad' => 'Radical Equations',
        'exp' => 'Exponential Functions',
        'log' => 'Logarithmic Functions',
    ];

    /**
     * The saved pre-test/post-test/activity answers for one student, scoped
     * to the teacher who owns that student's section.
     */
    public function index(User $student): JsonResponse
    {
        if (! $this->belongsToTeacher($student)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $attempts = StudentQuizAnswer::where('session_id', (string) $student->id)
            ->whereNotNull('answers')
            ->orderByDesc('updated_at')
            ->get(['topic_key', 'phase', 'answers', 'score', 'total', 'updated_at'])
            ->map(fn (StudentQuizAnswer $attempt) => [
                'topic_key' => $attempt->topic_key,
                'topic_name' => self::TOPIC_NAMES[$attempt->topic_key] ?? $attempt->topic_key,
                'phase' => $attempt->phase,
                'answers' => $attempt->answers,
                'score' => $attempt->score,
                'total' => $attempt->total,
                'updated_at' => $attempt->updated_at?->toIso8601String(),
            ]);

        return response()->json([
            'student' => ['id' => $student->id, 'name' => $student->name],
            'attempts' => $attempts,
        ]);
    }

    /**
     * A student's answers can only be viewed by the teacher who owns their section.
     */
    private function belongsToTeacher(User $student): bool
    {
        return $student->role === 'student'
            && $student->section_id !== null
            && $student->section?->teacher_id === Auth::id();
    }
}

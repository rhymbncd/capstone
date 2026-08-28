<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saved quiz drafts from the generator. Shared pool across all approved
 * teachers (no ownership column exists); authorisation is the role:teacher
 * route middleware.
 */
class QuizDraftController extends Controller
{
    private const LIST_LIMIT = 20;

    public function index(): JsonResponse
    {
        return response()->json([
            'drafts' => Quiz::orderByDesc('created_at')->limit(self::LIST_LIMIT)->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $draft = Quiz::create($this->validated($request));

        return response()->json(['draft' => $draft], 201);
    }

    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        $quiz->update($this->validated($request));

        return response()->json(['draft' => $quiz]);
    }

    public function destroy(Quiz $quiz): JsonResponse
    {
        $quiz->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'topic' => ['nullable', 'string', 'max:255'],
            'activity_label' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:50'],
            'difficulty' => ['nullable', 'string', 'max:50'],
            'pretest' => ['required', 'json'],
            'posttest' => ['required', 'json'],
            'activity' => ['required', 'json'],
        ]);
    }
}

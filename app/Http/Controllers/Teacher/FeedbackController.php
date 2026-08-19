<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherFeedback;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * List feedback this teacher has sent.
     */
    public function index(): JsonResponse
    {
        $feedback = TeacherFeedback::where('teacher_id', Auth::id())
            ->with('student:id,name')
            ->latest()
            ->get()
            ->map(fn (TeacherFeedback $f) => $this->toPayload($f));

        return response()->json(['feedback' => $feedback]);
    }

    /**
     * Send feedback to a student in one of this teacher's sections.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:users,id',
            'type' => 'required|in:encouragement,improvement,praise,reminder',
            'message' => 'required|string|min:5|max:500',
        ]);

        $student = User::find($validated['student_id']);

        $belongsToTeacher = $student
            && $student->role === 'student'
            && $student->section_id !== null
            && $student->section?->teacher_id === Auth::id();

        if (! $belongsToTeacher) {
            return response()->json([
                'message' => 'That student is not in one of your sections.',
            ], 403);
        }

        $feedback = TeacherFeedback::create([
            'teacher_id' => Auth::id(),
            'student_id' => $student->id,
            'type' => $validated['type'],
            'message' => $validated['message'],
        ]);

        $feedback->setRelation('student', $student);

        return response()->json(['feedback' => $this->toPayload($feedback)], 201);
    }

    /**
     * @return array{id: int, studentId: int, studentName: string, type: string, message: string, date: string, read: bool}
     */
    private function toPayload(TeacherFeedback $feedback): array
    {
        return [
            'id' => $feedback->id,
            'studentId' => $feedback->student_id,
            'studentName' => $feedback->student->name ?? 'Unknown',
            'type' => $feedback->type,
            'message' => $feedback->message,
            'date' => $feedback->created_at->diffForHumans(),
            'read' => $feedback->read_at !== null,
        ];
    }
}

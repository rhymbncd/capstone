<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TeacherFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * List feedback the authenticated student has received.
     */
    public function index(): JsonResponse
    {
        $feedback = TeacherFeedback::where('student_id', Auth::id())
            ->with('teacher:id,name')
            ->latest()
            ->get()
            ->map(fn (TeacherFeedback $f) => [
                'id' => $f->id,
                'teacherName' => $f->teacher->name ?? 'Your teacher',
                'type' => $f->type,
                'message' => $f->message,
                'date' => $f->created_at->diffForHumans(),
                'read' => $f->read_at !== null,
            ]);

        return response()->json(['feedback' => $feedback]);
    }

    /**
     * Mark every unread feedback item for this student as read.
     */
    public function markAllRead(): JsonResponse
    {
        TeacherFeedback::where('student_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }
}

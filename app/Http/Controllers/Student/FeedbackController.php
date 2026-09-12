<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TeacherFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * List feedback the authenticated student has received.
     *
     * Supports HTTP conditional requests (ETag/If-None-Match) so a client
     * polling this on an interval gets a cheap 304 with no body when
     * nothing has actually changed, instead of re-downloading the same
     * list every time.
     */
    public function index(Request $request): JsonResponse
    {
        $rows = TeacherFeedback::where('student_id', Auth::id())
            ->with('teacher:id,name')
            ->latest()
            ->get();

        $payload = $rows->map(fn (TeacherFeedback $f) => [
            'id' => $f->id,
            'teacherName' => $f->teacher->name ?? 'Your teacher',
            'sender' => $f->sender,
            'replyToId' => $f->reply_to_id,
            'type' => $f->type,
            'message' => $f->message,
            'date' => $f->created_at->diffForHumans(),
            'read' => $f->read_at !== null,
        ]);

        // The ETag is fingerprinted from id + read-state only — not the
        // rendered payload — because `date` ("3 minutes ago") changes every
        // minute on its own and would otherwise defeat 304 caching entirely
        // even when nothing about the feedback itself actually changed.
        $fingerprint = $rows
            ->map(fn (TeacherFeedback $f) => $f->id.':'.($f->read_at?->timestamp ?? 'unread'))
            ->implode('|');

        $response = response()->json(['feedback' => $payload]);
        $response->setEtag(md5($fingerprint));

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    /**
     * Send a plain message to this student's own teacher, threaded into
     * the same feed as the feedback that teacher sends them.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:5|max:500',
            'reply_to_id' => 'nullable|integer|exists:teacher_feedback,id',
        ]);

        $student = Auth::user();
        $teacher = $student->section?->teacher;

        if (! $teacher) {
            return response()->json([
                'message' => 'You are not assigned to a section with a teacher yet.',
            ], 422);
        }

        $replyToId = null;
        if (! empty($validated['reply_to_id'])) {
            // Only allow replying to a message that's actually part of this
            // student's own conversation with their own teacher — not an
            // arbitrary feedback row belonging to someone else.
            $parent = TeacherFeedback::where('id', $validated['reply_to_id'])
                ->where('teacher_id', $teacher->id)
                ->where('student_id', $student->id)
                ->first();

            $replyToId = $parent?->id;
        }

        $feedback = TeacherFeedback::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'sender' => 'student',
            'reply_to_id' => $replyToId,
            'type' => 'message',
            'message' => $validated['message'],
            // A student's own sent message was never "unread" from their
            // side — only a teacher's reply should trip their badge.
            'read_at' => now(),
        ]);

        return response()->json([
            'feedback' => [
                'id' => $feedback->id,
                'teacherName' => $teacher->name,
                'sender' => 'student',
                'replyToId' => $feedback->reply_to_id,
                'type' => 'message',
                'message' => $feedback->message,
                'date' => $feedback->created_at->diffForHumans(),
                'read' => true,
            ],
        ], 201);
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

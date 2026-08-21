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

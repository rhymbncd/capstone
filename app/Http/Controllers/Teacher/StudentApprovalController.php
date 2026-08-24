<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentApprovalController extends Controller
{
    private const PENDING_PER_PAGE = 15;

    public function index(): View
    {
        $teacherId = Auth::id();

        $ownSection = fn ($query) => $query->where('teacher_id', $teacherId);

        $pendingStudents = User::where('role', 'student')
            ->where('approval_status', 'pending')
            ->whereHas('section', $ownSection)
            ->latest()
            ->paginate(15);

        $approvedStudents = User::where('role', 'student')
            ->where('approval_status', 'approved')
            ->whereHas('section', $ownSection)
            ->with('section:id,name') // the Approved table shows Section — avoid an N+1 per row
            ->latest()
            ->paginate(15);

        $rejectedStudents = User::where('role', 'student')
            ->where('approval_status', 'rejected')
            ->whereHas('section', $ownSection)
            ->latest()
            ->paginate(15);

        return view('teacher.student-approvals.index', compact('pendingStudents', 'approvedStudents', 'rejectedStudents'));
    }

    /**
     * Live-poll payload for the approvals page: pending queue (page 1 only,
     * matching what the page shows before anyone paginates) plus counts for
     * all three statuses.
     *
     * Supports HTTP conditional requests (ETag/If-None-Match) so a client
     * polling this on an interval gets a cheap 304 with no body when
     * nothing has actually changed.
     */
    public function approvalsData(Request $request): JsonResponse
    {
        $teacherId = Auth::id();
        $ownSection = fn ($query) => $query->where('teacher_id', $teacherId);

        $pendingQuery = User::where('role', 'student')->where('approval_status', 'pending')->whereHas('section', $ownSection);
        $pending = (clone $pendingQuery)->orderByDesc('created_at')->limit(self::PENDING_PER_PAGE)->get(['id', 'name', 'email', 'student_id', 'created_at']);
        $pendingCount = $pendingQuery->count();
        $approvedCount = User::where('role', 'student')->where('approval_status', 'approved')->whereHas('section', $ownSection)->count();
        $rejectedCount = User::where('role', 'student')->where('approval_status', 'rejected')->whereHas('section', $ownSection)->count();

        $payload = [
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'pending' => $pending->map(fn (User $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'studentId' => $student->student_id,
                'requestedAgo' => $student->created_at->diffForHumans(),
                'approveUrl' => route('teacher.student.approve', $student->id),
                'rejectUrl' => route('teacher.student.reject', $student->id),
            ]),
        ];

        // Fingerprinted from the pending rows' ids/timestamps plus all three
        // counts — not the rendered payload (which includes "X minutes ago"
        // style text that drifts on its own and would defeat 304 caching).
        $fingerprint = $pending
            ->map(fn (User $student) => $student->id.':'.$student->created_at->timestamp)
            ->implode('|').'#'.$pendingCount.'#'.$approvedCount.'#'.$rejectedCount;

        $response = response()->json($payload);
        $response->setEtag(md5($fingerprint));

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    public function approve(User $user): RedirectResponse
    {
        if (! $this->belongsToTeacher($user)) {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'approved']);

        ActivityLog::record('system', 'Student Approved', "{$user->name} was approved by their teacher", user: $user);

        return back()->with('success', "{$user->name} has been approved and can now log in.");
    }

    public function reject(User $user): RedirectResponse
    {
        if (! $this->belongsToTeacher($user)) {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'rejected']);

        ActivityLog::record('system', 'Student Rejected', "{$user->name} was rejected by their teacher", user: $user);

        return back()->with('success', "{$user->name} has been rejected.");
    }

    public function reset(User $user): RedirectResponse
    {
        if (! $this->belongsToTeacher($user)) {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'pending']);

        return back()->with('success', "{$user->name} status has been reset to pending.");
    }

    /**
     * A student can only be managed by the teacher who owns their section.
     */
    private function belongsToTeacher(User $user): bool
    {
        return $user->role === 'student'
            && $user->section_id !== null
            && $user->section?->teacher_id === Auth::id();
    }
}

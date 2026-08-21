<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherApprovalController extends Controller
{
    private const PENDING_PER_PAGE = 15;

    public function index(): View
    {
        $pendingTeachers = User::where('role', 'teacher')
            ->where('approval_status', 'pending')
            ->latest()
            ->paginate(15);

        $approvedTeachers = User::where('role', 'teacher')
            ->where('approval_status', 'approved')
            ->latest()
            ->paginate(15);

        $rejectedTeachers = User::where('role', 'teacher')
            ->where('approval_status', 'rejected')
            ->latest()
            ->paginate(15);

        return view('admin.teacher-approvals.index', compact('pendingTeachers', 'approvedTeachers', 'rejectedTeachers'));
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
        $pendingQuery = User::where('role', 'teacher')->where('approval_status', 'pending');
        $pending = (clone $pendingQuery)->orderByDesc('created_at')->limit(self::PENDING_PER_PAGE)->get(['id', 'name', 'email', 'created_at']);
        $pendingCount = $pendingQuery->count();
        $approvedCount = User::where('role', 'teacher')->where('approval_status', 'approved')->count();
        $rejectedCount = User::where('role', 'teacher')->where('approval_status', 'rejected')->count();

        $payload = [
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'pending' => $pending->map(fn (User $teacher) => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'requestedAgo' => $teacher->created_at->diffForHumans(),
                'approveUrl' => route('admin.teacher.approve', $teacher->id),
                'rejectUrl' => route('admin.teacher.reject', $teacher->id),
            ]),
        ];

        // Fingerprinted from the pending rows' ids/timestamps plus all three
        // counts — not the rendered payload (which includes "X minutes ago"
        // style text that drifts on its own and would defeat 304 caching).
        $fingerprint = $pending
            ->map(fn (User $teacher) => $teacher->id.':'.$teacher->created_at->timestamp)
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
        if ($user->role !== 'teacher') {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'approved']);

        ActivityLog::record('system', 'Teacher Approved', "{$user->name} was approved by an admin", user: $user);

        return back()->with('success', "{$user->name} has been approved and can now log in.");
    }

    public function reject(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'rejected']);

        ActivityLog::record('system', 'Teacher Rejected', "{$user->name} was rejected by an admin", user: $user);

        return back()->with('success', "{$user->name} has been rejected.");
    }

    public function reset(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'pending']);

        return back()->with('success', "{$user->name} status has been reset to pending.");
    }
}

<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentApprovalController extends Controller
{
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
            ->latest()
            ->paginate(15);

        $rejectedStudents = User::where('role', 'student')
            ->where('approval_status', 'rejected')
            ->whereHas('section', $ownSection)
            ->latest()
            ->paginate(15);

        return view('teacher.student-approvals.index', compact('pendingStudents', 'approvedStudents', 'rejectedStudents'));
    }

    public function approve(User $user): RedirectResponse
    {
        if (! $this->belongsToTeacher($user)) {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'approved']);

        return back()->with('success', "{$user->name} has been approved and can now log in.");
    }

    public function reject(User $user): RedirectResponse
    {
        if (! $this->belongsToTeacher($user)) {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'rejected']);

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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeacherApprovalController extends Controller
{
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

    public function approve(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'approved']);

        ActivityLog::record('system', 'Teacher Approved', "{$user->name} was approved by an admin");

        return back()->with('success', "{$user->name} has been approved and can now log in.");
    }

    public function reject(User $user): RedirectResponse
    {
        if ($user->role !== 'teacher') {
            return back()->withErrors(['error' => 'Invalid user role.']);
        }

        $user->update(['approval_status' => 'rejected']);

        ActivityLog::record('system', 'Teacher Rejected', "{$user->name} was rejected by an admin");

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

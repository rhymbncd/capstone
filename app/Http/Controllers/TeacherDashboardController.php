<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    public function index(): Response
    {
        $pendingStudents = User::where('role', 'student')
            ->where('approval_status', 'pending')
            ->whereHas('section', fn ($query) => $query->where('teacher_id', Auth::id()))
            ->latest()
            ->get();

        // This authenticated dashboard shell must never be served from the
        // browser's back/forward or disk cache — otherwise a deploy that
        // changes the markup (new metric card, etc.) keeps showing the old
        // page until the cache happens to expire.
        return response()
            ->view('dashboard.teacher_dashboard', compact('pendingStudents'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}

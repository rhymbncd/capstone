<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function index(): View
    {
        $pendingStudents = User::where('role', 'student')
            ->where('approval_status', 'pending')
            ->whereHas('section', fn ($query) => $query->where('teacher_id', Auth::id()))
            ->latest()
            ->get();

        return view('dashboard.teacher_dashboard', compact('pendingStudents'));
    }
}

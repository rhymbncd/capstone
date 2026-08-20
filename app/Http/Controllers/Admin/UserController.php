<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentProgress;
use App\Models\StudentQuizAnswer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List every registered user for the admin "User Management" page.
     */
    public function index(): JsonResponse
    {
        $users = User::orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'student_id', 'role', 'approval_status', 'created_at'])
            ->map(fn (User $user) => $this->toPayload($user));

        return response()->json(['users' => $users]);
    }

    /**
     * Update a user's name, email, role, and active status.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'You cannot edit your own account from this panel.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => 'required|in:student,teacher,admin',
            'status' => 'required|in:Active,Inactive',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->approval_status = $validated['status'] === 'Active' ? 'approved' : 'rejected';

        // A user who is no longer a student shouldn't keep a section assignment.
        if ($user->role !== 'student') {
            $user->section_id = null;
        }

        $user->save();

        return response()->json(['user' => $this->toPayload($user)]);
    }

    /**
     * Permanently remove a user account.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last remaining admin account.',
            ], 422);
        }

        DB::transaction(function () use ($user) {
            // student_progress / student_quiz_answers reference the user via
            // a plain session_id string, not a real foreign key, so their
            // rows would otherwise be orphaned forever — silently skewing
            // analytics like Subject Completion Rates with progress that
            // belongs to a deleted account. teacher_feedback already
            // cascades via a real FK constraint.
            $sessionId = (string) $user->id;
            StudentProgress::where('session_id', $sessionId)->delete();
            StudentQuizAnswer::where('session_id', $sessionId)->delete();

            $user->delete();
        });

        return response()->json(['message' => 'User deleted successfully.']);
    }

    /**
     * @return array{id: int, name: string, email: string, studentId: ?string, role: string, status: string, joined: string}
     */
    private function toPayload(User $user): array
    {
        $status = match ($user->approval_status) {
            'approved' => 'Active',
            'rejected' => 'Inactive',
            default => 'Pending',
        };

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'studentId' => $user->student_id,
            'role' => $user->role,
            'status' => $status,
            'joined' => $user->created_at->format('M j, Y'),
        ];
    }
}

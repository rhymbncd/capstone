<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\StudentProgress;
use App\Models\StudentQuizAnswer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const PER_PAGE = 25;

    private const COUNTS_CACHE_KEY = 'admin.users.counts';

    private const COUNTS_CACHE_TTL = 300; // 5 minutes

    /**
     * Paginated, searchable "User Management" listing. Also the source of
     * the Home tab's user-count tiles, via `counts` — computed separately
     * from the search/role filters below (always platform-wide) so a
     * lingering search on the Users tab can never make the Home tab's
     * tiles look wrong.
     *
     * Supports HTTP conditional requests (ETag/If-None-Match) so a client
     * polling this on an interval gets a cheap 304 with no body when
     * nothing on this exact page/filter combination has actually changed.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'role' => ['nullable', 'string', 'in:student,teacher,admin'],
            'page' => 'nullable|integer|min:1',
        ]);

        $query = User::query();

        if ($validated['q'] ?? null) {
            $term = $validated['q'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($validated['role'] ?? null) {
            $query->where('role', $validated['role']);
        }

        $users = $query->orderByDesc('created_at')
            ->paginate(self::PER_PAGE, ['id', 'name', 'email', 'student_id', 'role', 'approval_status', 'created_at', 'updated_at'])
            ->withQueryString();

        // Home tab tiles poll this every 30s across however many admins have
        // it open — role/status counts only change on a rare, deliberate
        // admin action (registration+approval, edit, delete), not on every
        // request, so a short cache absorbs that polling instead of running
        // 4 count() queries per tick. Busted explicitly in update()/destroy()
        // below so the admin who just acted sees the real number immediately
        // rather than waiting out the TTL.
        $counts = Cache::remember(self::COUNTS_CACHE_KEY, self::COUNTS_CACHE_TTL, fn () => [
            'total' => User::count(),
            'students' => User::where('role', 'student')->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'pending' => User::where('approval_status', 'pending')->count(),
        ]);

        $payload = collect($users->items())->map(fn (User $user) => $this->toPayload($user));

        // Fingerprinted from the current page's id/role/approval/updated_at
        // plus the counts, not the rendered payload — not "X minutes ago"
        // style text (there isn't any here, but same principle as the
        // other polled endpoints: fingerprint stable data, not prose).
        $fingerprint = collect($users->items())
            ->map(fn (User $user) => $user->id.':'.$user->role.':'.$user->approval_status.':'.$user->updated_at->timestamp)
            ->implode('|').'#'.implode(',', $counts);

        $response = response()->json([
            'users' => $payload,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ],
            'counts' => $counts,
        ]);
        $response->setEtag(md5($fingerprint));

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
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

        ActivityLog::record(
            'system',
            'User Account Edited',
            "{$user->name} ({$user->email}) — role: {$user->role}, status: {$user->approval_status}",
            user: Auth::user(),
        );

        // Role/status may have just changed — don't make the admin who did
        // it wait out the 5-minute TTL to see the Home tab tiles agree.
        Cache::forget(self::COUNTS_CACHE_KEY);

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

        $deletedDescription = "{$user->name} ({$user->email}) — was {$user->role}";

        DB::transaction(function () use ($user) {
            // student_progress / student_quiz_answers reference the user via
            // a plain session_id string, not a real foreign key, so their
            // rows would otherwise be orphaned forever — silently skewing
            // analytics like Subject Completion Rates with progress that
            // belongs to a deleted account. teacher_feedback and sections
            // already cascade via real FK constraints.
            $sessionId = (string) $user->id;
            StudentProgress::where('session_id', $sessionId)->delete();
            StudentQuizAnswer::where('session_id', $sessionId)->delete();

            // activity_logs.user_id normally just gets nulled by its FK
            // (SET NULL), keeping the entry as history. Deleting the account
            // should erase every trace of it, so the entries themselves go
            // too — must happen before $user->delete() while user_id is
            // still set, since that's how we find them.
            ActivityLog::where('user_id', $user->id)->delete();

            $user->delete();
        });

        ActivityLog::record('system', 'User Account Deleted', $deletedDescription, user: Auth::user());

        Cache::forget(self::COUNTS_CACHE_KEY);

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

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (! Auth::check() || Auth::user()->role !== $role) {
            return redirect()->route('homepage')
                ->withErrors(['access' => 'Unauthorized access.']);
        }

        // A teacher whose approval is revoked (rejected / reset to pending)
        // after they logged in must lose dashboard access on their next
        // request, not only when they choose to log out. Admins have no
        // approval gate, so this only applies to teachers.
        if ($role === 'teacher' && Auth::user()->approval_status !== 'approved') {
            Auth::logout();

            return redirect()->route('teacher.login')
                ->withErrors(['email' => 'Your account is awaiting administrator approval.']);
        }

        return $next($request);
    }
}

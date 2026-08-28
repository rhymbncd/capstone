<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Show the "forgot password" form for a given portal (student|teacher).
     */
    public function showForgotForm(string $portalType): View
    {
        return view('login.forgot-password', ['portalType' => $portalType]);
    }

    /**
     * Email a password reset link, scoped to the account matching this
     * portal's role, so a teacher can't be reset from the student portal
     * or vice versa. Always responds with the same generic message so the
     * form can't be used to enumerate which emails have an account.
     */
    public function sendResetLink(Request $request, string $portalType): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink([
            'email' => $request->email,
            'role' => $portalType,
        ]);

        return back()->with('success', 'If an account exists for that email, a password reset link has been sent.');
    }

    /**
     * Show the "set a new password" form.
     *
     * Parameter order matters here: Laravel fills unresolved route
     * parameters positionally (URI segments first, then ->defaults()
     * values), so $token (from the {token} URI segment) must come
     * before $portalType (a route default) to bind correctly.
     */
    public function showResetForm(Request $request, string $token, string $portalType): View
    {
        return view('login.reset-password', [
            'portalType' => $portalType,
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Reset the account's password using the emailed token.
     */
    public function resetPassword(Request $request, string $portalType): RedirectResponse
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            [...$validated, 'role' => $portalType],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route("{$portalType}.login")
                ->with('success', 'Your password has been reset. Please sign in.');
        }

        return back()
            ->withErrors(['email' => 'That password reset link is invalid or has expired. Please request a new one.'])
            ->onlyInput('email');
    }
}

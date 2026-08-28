<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL; // <-- Siguraduhing naidagdag itong linya na ito
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pwersahing maging HTTPS ang lahat ng assets at links kapag nasa production (Railway/Render)
        if (config('app.env') === 'production' || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Local-only: an accessed-but-not-eager-loaded relationship throws
        // instead of silently firing a query, so an N+1 fails loudly in
        // dev/tests rather than just showing up as a slow request in prod.
        Model::preventLazyLoading(! app()->isProduction());

        // Send teachers/students to their own portal's reset-password page,
        // instead of Laravel's single default `password.reset` route.
        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            $portal = in_array($user->role, ['teacher', 'student'], true) ? $user->role : 'student';

            return route("{$portal}.password.reset", ['token' => $token, 'email' => $user->email]);
        });

        // Targeted brute-force protection: 5 tries/min against any single
        // account. The looser per-IP cap still lets a full computer lab sign
        // in at the start of class while stopping automated credential spray.
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return [
                Limit::perMinute(5)->by($key),
                Limit::perMinute(60)->by($request->ip()),
            ];
        });

        // Registration, password-reset request/submit, Google OAuth.
        RateLimiter::for('auth-actions', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Paid AI calls — per authenticated teacher.
        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
    }
}

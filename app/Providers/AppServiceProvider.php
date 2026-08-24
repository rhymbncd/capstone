<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL; // <-- Siguraduhing naidagdag itong linya na ito
use Illuminate\Support\ServiceProvider;

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
    }
}

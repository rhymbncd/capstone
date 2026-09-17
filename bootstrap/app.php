<?php

use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StudentMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware as MiddlewareConfig;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as FrameworkPreventRequestsDuringMaintenance;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (MiddlewareConfig $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'student' => StudentMiddleware::class,
        ]);

        // This app has three separate login portals and no route named
        // "login", so the framework default (route('login')) would 500.
        // Send guests to the portal matching the URL they tried to reach.
        $middleware->redirectGuestsTo(function (Request $request) {
            return match (true) {
                $request->is('teacher*') => route('teacher.login'),
                $request->is('admin*') => route('admin.login'),
                default => route('student.login'),
            };
        });

        // Keep the admin portal reachable during maintenance mode, so the
        // admin who enabled it isn't locked out of turning it back off.
        $middleware->replace(FrameworkPreventRequestsDuringMaintenance::class, PreventRequestsDuringMaintenance::class);

        // Real X-Frame-Options/X-Content-Type-Options/Referrer-Policy
        // headers on every response, replacing the <meta http-equiv> tags
        // that browsers never actually enforced for those header names.
        $middleware->append(SecurityHeaders::class);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Session/CSRF tokens expire after long idle periods (e.g. a
        // student taking a while on an activity). Laravel converts a
        // TokenMismatchException to a plain HttpException(419) before any
        // render() callback runs, so it must be caught here by status code
        // rather than by exception type. Instead of showing Laravel's raw
        // "419 Page Expired" screen, send the user back to what they were
        // doing with a friendly message so they can retry.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please try again.',
                ], 419);
            }

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['error' => 'Your session expired. Please try again.']);
        });
    })->create();

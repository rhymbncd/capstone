<?php

use Illuminate\Support\Facades\Vite;

use function Pest\Laravel\get;

it('sets real security headers on every response', function () {
    $response = get('/');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('sends a Content-Security-Policy that requires a nonce for scripts, not unsafe-inline', function () {
    // Phase D of the CSP refactor: every inline handler in the app is now
    // addEventListener-wired and every inline <script> carries this nonce,
    // so script-src no longer needs 'unsafe-inline' — see the comment on
    // SecurityHeaders::contentSecurityPolicy().
    $response = get('/');

    $response->assertHeader('Content-Security-Policy');
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toMatch('/script-src[^;]*\'nonce-'.preg_quote(Vite::cspNonce(), '/')."'/")
        ->not->toContain("script-src 'self' 'unsafe-inline'")
        ->not->toMatch('/script-src[^;]*strict-dynamic/')
        ->toContain("frame-ancestors 'self'");
});

it('stamps every @vite(...) tag and inline <script> with the same nonce the header requires', function () {
    $response = get('/');
    $csp = $response->headers->get('Content-Security-Policy');
    $html = $response->getContent();

    expect($csp)->toContain("'nonce-".Vite::cspNonce()."'");
    expect($html)->toContain('nonce="'.Vite::cspNonce().'"');
});

it('removes the diagnostic routes that used to be public and unauthenticated', function () {
    get('/api/test')->assertNotFound();
    get('/supabase-test')->assertNotFound();
});

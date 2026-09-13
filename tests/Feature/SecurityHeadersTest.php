<?php

use Illuminate\Support\Facades\Vite;

use function Pest\Laravel\get;

it('sets real security headers on every response', function () {
    $response = get('/');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('sends a Content-Security-Policy driven by nonce + strict-dynamic, not the host allowlist', function () {
    // Phase D of the CSP refactor: every inline handler is addEventListener-
    // wired and every inline/external <script> carries this nonce, so
    // 'strict-dynamic' can do the real trust decision for capable browsers.
    // 'unsafe-inline' and the CDN hosts stay in script-src too, but only as
    // a graceful-degradation fallback for browsers old enough to not
    // understand nonce-source/strict-dynamic syntax — a nonce-aware browser
    // ignores both the instant 'strict-dynamic' is present. See the comment
    // on SecurityHeaders::contentSecurityPolicy().
    $response = get('/');

    $response->assertHeader('Content-Security-Policy');
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toMatch('/script-src[^;]*\'nonce-'.preg_quote(Vite::cspNonce(), '/')."'/")
        ->toMatch("/script-src[^;]*'strict-dynamic'/")
        ->toMatch("/script-src[^;]*'unsafe-inline'/")
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

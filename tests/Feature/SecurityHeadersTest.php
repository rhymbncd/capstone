<?php

use Illuminate\Support\Facades\Vite;

use function Pest\Laravel\get;

it('sets real security headers on every response', function () {
    $response = get('/');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('sends a Content-Security-Policy that still allows unsafe-inline for scripts', function () {
    // Deliberately no nonce source in script-src yet — see the comment on
    // SecurityHeaders::contentSecurityPolicy(). Adding one without first
    // converting every onclick="..." attribute in the app would break all
    // of them at once, since nonce-aware browsers stop honoring
    // 'unsafe-inline' the instant a nonce source is present.
    $response = get('/');

    $response->assertHeader('Content-Security-Policy');
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("script-src 'self' 'unsafe-inline'")
        ->not->toMatch("/script-src[^;]*'nonce-/")
        ->toContain("frame-ancestors 'self'");
});

it('stamps every @vite(...) tag with a CSP nonce, ready for once script-src requires one', function () {
    $html = get('/')->getContent();

    expect($html)->toContain('nonce="'.Vite::cspNonce().'"');
});

it('removes the diagnostic routes that used to be public and unauthenticated', function () {
    get('/api/test')->assertNotFound();
    get('/supabase-test')->assertNotFound();
});

<?php

use function Pest\Laravel\get;

it('sets real security headers on every response', function () {
    $response = get('/');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('removes the diagnostic routes that used to be public and unauthenticated', function () {
    get('/api/test')->assertNotFound();
    get('/supabase-test')->assertNotFound();
});

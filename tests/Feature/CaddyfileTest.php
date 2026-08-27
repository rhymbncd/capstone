<?php

beforeEach(function () {
    $this->caddyfile = file_get_contents(base_path('docker/Caddyfile'));
});

it('compresses responses with zstd and gzip', function () {
    expect($this->caddyfile)->toContain('encode zstd gzip');
});

it('caches Vite build output for a year as immutable', function () {
    expect($this->caddyfile)
        ->toContain('@immutable path /build/*')
        ->toMatch('/header @immutable Cache-Control "public, max-age=31536000, immutable"/');
});

it('caches fonts for a year without immutable', function () {
    expect($this->caddyfile)
        ->toContain('@fonts path /fonts/*')
        ->toMatch('/header @fonts Cache-Control "public, max-age=31536000"/');
});

it('caches images for thirty days', function () {
    expect($this->caddyfile)
        ->toContain('@images path /image/*')
        ->toMatch('/header @images Cache-Control "public, max-age=2592000"/');
});

it('does not attach a long-lived cache header to HTML documents', function () {
    expect($this->caddyfile)->not->toContain('header Cache-Control');
});

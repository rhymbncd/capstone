<?php

beforeEach(function () {
    $this->caddyfile = file_get_contents(base_path('docker/Caddyfile'));
});

it('compresses responses with zstd and gzip', function () {
    expect($this->caddyfile)->toContain('encode zstd gzip');
});

it('emits a permanent marker header identifying the active config', function () {
    expect($this->caddyfile)->toContain('header X-Config-Source "app-caddyfile"');
});

it('sends HSTS and COOP security headers', function () {
    expect($this->caddyfile)
        ->toContain('header Strict-Transport-Security "max-age=31536000; includeSubDomains"')
        ->toContain('header Cross-Origin-Opener-Policy "same-origin"');
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

it('is installed at the path FrankenPHP loads by default', function () {
    expect(file_get_contents(base_path('Dockerfile')))
        ->toContain('COPY docker/Caddyfile /etc/frankenphp/Caddyfile');
});

it('is still loaded explicitly by the entrypoint', function () {
    expect(file_get_contents(base_path('docker/entrypoint.sh')))
        ->toContain('--config /app/docker/Caddyfile');
});

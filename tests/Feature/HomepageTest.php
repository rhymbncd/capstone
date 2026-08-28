<?php

use App\Models\PlatformSetting;

use function Pest\Laravel\get;

it('loads the homepage successfully', function () {
    get('/')->assertOk();
});

it('shows the default platform description when none has been saved', function () {
    get('/')->assertSee('Interactive learning platform for Junior High School Mathematics at Bubog National High School');
});

it('shows the admin-saved platform description on the homepage', function () {
    PlatformSetting::create(['key' => 'platform_desc', 'value' => 'A brand new custom description for MathLearn.']);

    get('/')->assertSee('A brand new custom description for MathLearn.');
});

it('serves the hero image as a responsive, non-lazy LCP element', function () {
    $html = get('/')->getContent();

    foreach (['640w', '1280w', '1920w'] as $variant) {
        expect($html)->toContain("/image/pexels-photo-6344238-{$variant}.webp {$variant}");
        expect(file_exists(public_path("image/pexels-photo-6344238-{$variant}.webp")))->toBeTrue();
    }

    expect($html)
        ->toContain('sizes="100vw"')
        ->toContain('width="1920" height="1280"')
        ->toContain('fetchpriority="high"')
        ->toContain('decoding="async"')
        ->not->toContain('loading="lazy"');
});

it('preloads the hero image and the self-hosted font in the head', function () {
    $html = get('/')->getContent();

    expect($html)
        ->toContain('rel="preload" as="image"')
        ->toContain('href="/image/pexels-photo-6344238-1280w.webp"')
        ->toContain('imagesrcset=')
        ->toContain('rel="preload" href="/fonts/inter-latin-400-800.woff2" as="font" type="font/woff2" crossorigin');
});

it('declares font-display: swap for the self-hosted Inter face', function () {
    expect(file_get_contents(resource_path('css/homepage.css')))
        ->toContain('font-display: swap');
});

it('has no infinite animation dragging main-thread work', function () {
    expect(file_get_contents(resource_path('css/homepage.css')))
        ->not->toContain('infinite');
});

it('inlines the homepage stylesheet instead of a render-blocking link', function () {
    $html = get('/')->getContent();

    expect($html)
        ->toContain('<style>')
        ->toContain('font-display:swap')
        ->not->toMatch('/<link[^>]+rel="stylesheet"[^>]+homepage-[^"]+\.css/');
});

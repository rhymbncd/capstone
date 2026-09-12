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

it('limits infinite animation to a motion-safe, GPU-composited scroll cue', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    preg_match_all('/animation:[^;]*\binfinite\b[^;]*;/', $css, $infinite);
    expect($infinite[0])->toHaveCount(1);

    preg_match_all('/@media \(prefers-reduced-motion: no-preference\)\s*\{(?:[^{}]|\{[^{}]*\})*\}/', $css, $motionSafe);
    expect(collect($motionSafe[0])->contains(fn (string $b): bool => str_contains($b, 'infinite')))->toBeTrue();

    preg_match('/@keyframes heroScrollBounce\s*\{(?:[^{}]|\{[^{}]*\})*\}/', $css, $keyframes);
    expect($keyframes[0] ?? '')
        ->toContain('transform')
        ->not->toContain('opacity')
        ->not->toContain('width')
        ->not->toContain('box-shadow');
});

it('never starts the LCP hero text at opacity zero', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    preg_match('/\.hero-content h1\s*\{[^}]*\}/', $css, $h1);
    preg_match('/@keyframes fadeUp\s*\{(?:[^{}]|\{[^{}]*\})*\}/', $css, $fadeUp);

    expect($h1[0] ?? '')->not->toContain('opacity');
    expect($fadeUp[0] ?? '')->toContain('transform')->not->toContain('opacity');
});

it('marks the document as js-capable so reveal sections degrade gracefully', function () {
    expect(get('/')->getContent())->toContain("classList.add('js')");
});

it('is written mobile-first with min-width breakpoints only', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    expect($css)->not->toMatch('/@media\s*\(\s*max-width/');

    foreach (['640px', '768px', '1024px'] as $breakpoint) {
        expect($css)->toMatch('/@media\s*\(min-width:\s*'.preg_quote($breakpoint, '/').'\)/');
    }
});

it('constrains section content with a shared max-width container', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    preg_match('/\.feature-grid\s*\{[^}]*\}/', $css, $featureGrid);
    preg_match('/\.topics-grid\s*\{[^}]*\}/', $css, $topicsGrid);

    expect($featureGrid[0] ?? '')->toContain('max-width')->toContain('margin: 0 auto');
    expect($topicsGrid[0] ?? '')->toContain('max-width');
});

it('never sizes body copy below 16px', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    $bodySelectors = [
        '.hero-content p',
        '.section-desc',
        '.feature-card p',
        '.topic-card p',
        '.subtitle',
        '.footer p',
    ];

    foreach ($bodySelectors as $selector) {
        preg_match('/'.preg_quote($selector, '/').'\s*\{[^}]*\}/', $css, $rule);

        expect($rule[0] ?? '')->toMatch('/font-size:\s*1(\.[0-9]+)?rem/');
    }
});

it('gives the hero call-to-action buttons a 44px+ touch target', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    preg_match('/\.btn\s*\{[^}]*\}/', $css, $btn);

    expect($btn[0] ?? '')->toMatch('/min-height:\s*4[4-9]px|min-height:\s*[5-9][0-9]px/');
});

it('stacks the hero buttons full-width on mobile and inline from sm up', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    preg_match('/\.btn\s*\{[^}]*\}/', $css, $btn);
    preg_match_all('/@media\s*\(min-width:\s*640px\)\s*\{(?:[^{}]|\{[^{}]*\})*\}/', $css, $smBlocks);

    $btnGoesAutoAtSm = collect($smBlocks[0])->contains(
        fn (string $block): bool => str_contains($block, '.btn') && str_contains($block, 'width: auto'),
    );

    expect($btn[0] ?? '')->toContain('width: 100%');
    expect($btnGoesAutoAtSm)->toBeTrue();
});

it('sizes the hero to the small viewport height so mobile chrome cannot clip it', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    preg_match('/\.hero\s*\{[^}]*\}/', $css, $hero);

    expect($hero[0] ?? '')
        ->toContain('min-height: 100svh')
        ->toContain('min-height: 100vh')
        ->toContain('overflow: hidden')
        ->toContain('display: flex');
});

it('darkens the hero photo with a flat layer above the gradient for headline contrast', function () {
    $html = get('/')->getContent();
    $css = file_get_contents(resource_path('css/homepage.css'));

    expect($html)->toContain('hero-overlay');

    preg_match('/\.hero-overlay\s*\{[^}]*\}/', $css, $overlay);
    expect($overlay[0] ?? '')
        ->toContain('rgba(0, 0, 0, 0.25)')
        ->toContain('inset: 0');
});

it('renders an accessible animated scroll indicator that targets the features section', function () {
    $html = get('/')->getContent();
    $css = file_get_contents(resource_path('css/homepage.css'));

    expect($html)
        ->toContain('href="#features"')
        ->toContain('aria-label="Scroll to features"')
        ->toMatch('/<a[^>]+class="hero-scroll"[^>]*>\s*<svg/')
        ->toContain('id="features"');

    // Double-chevron icon (two <path> elements), no circular badge around it.
    preg_match('/<a[^>]+class="hero-scroll"[^>]*>.*?<\/a>/s', $html, $link);
    expect(substr_count($link[0] ?? '', '<path'))->toBe(2);

    preg_match('/\.hero-scroll\s*\{[^}]*\}/', $css, $scroll);
    expect($scroll[0] ?? '')
        ->toContain('position: absolute')
        ->toContain('bottom: 32px')
        ->toContain('width: 48px')
        ->toContain('height: 48px')
        ->not->toContain('border-radius: 50%');
});

it('offsets the features anchor so a sticky header cannot cover its heading', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    preg_match('/\.features\s*\{[^}]*\}/', $css, $features);
    expect($features[0] ?? '')->toContain('scroll-margin-top');
});

it('enables smooth anchor scrolling only when motion is not reduced', function () {
    $css = file_get_contents(resource_path('css/homepage.css'));

    expect($css)->toMatch(
        '/@media \(prefers-reduced-motion: no-preference\)\s*\{\s*html\s*\{\s*scroll-behavior: smooth;/'
    );
});

it('inlines the homepage stylesheet instead of a render-blocking link', function () {
    $html = get('/')->getContent();

    expect($html)
        ->toContain('<style>')
        ->toContain('font-display:swap')
        ->not->toMatch('/<link[^>]+rel="stylesheet"[^>]+homepage-[^"]+\.css/');
});

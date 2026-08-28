<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <script>document.documentElement.classList.add('js')</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="MathLearn is an AI-powered math learning platform for junior high school students, featuring interactive modules, quizzes, and progress tracking.">

    <title>MATHsaLOVE</title>

    <!-- ================= SELF-HOSTED FONT ================= -->
    <link rel="preload" href="/fonts/inter-latin-400-800.woff2" as="font" type="font/woff2" crossorigin>

    <!-- ================= HERO IMAGE ================= -->
    <link rel="preload" as="image" type="image/webp"
          href="/image/pexels-photo-6344238-1280w.webp"
          imagesrcset="/image/pexels-photo-6344238-640w.webp 640w,
                       /image/pexels-photo-6344238-1280w.webp 1280w,
                       /image/pexels-photo-6344238-1920w.webp 1920w"
          imagesizes="100vw"
          fetchpriority="high">

    <!-- ================= CSS / JS ================= -->
    {{-- homepage.css is the whole page's styling and only ~3 KB gzipped, so
         it's inlined to keep it off the critical request path (no
         render-blocking stylesheet). Falls back to a linked tag while the
         Vite dev server is running. --}}
    @if (app(\Illuminate\Foundation\Vite::class)->isRunningHot())
        @vite('resources/css/homepage.css')
    @else
        <style>{!! \Illuminate\Support\Facades\Vite::content('resources/css/homepage.css') !!}</style>
    @endif

    @vite([
        'resources/js/homepage.js',
        'resources/js/nav-progress.js'
    ])

</head>

<body>

<main>

<!-- ================= HERO ================= -->
<section class="hero">

    <picture>
        <source
            type="image/webp"
            srcset="/image/pexels-photo-6344238-640w.webp 640w,
                    /image/pexels-photo-6344238-1280w.webp 1280w,
                    /image/pexels-photo-6344238-1920w.webp 1920w"
            sizes="100vw">
        <img src="/image/pexels-photo-6344238.jpeg" alt="" class="hero-bg"
             width="1920" height="1280"
             fetchpriority="high" decoding="async">
    </picture>

    <div class="hero-blur"></div>
    <div class="hero-gradient"></div>

    <div class="hero-content">

        <h1>MathLearn</h1>

        <h2>Math Learning Assistant</h2>

        <p>
            {{ $platformDescription }}
        </p>

        <div class="hero-actions">

            <a href="{{ route('signin-signin') }}" class="btn student">
                <i aria-hidden="true"><svg viewBox="0 0 512 512"><path d="M217.9 105.9L340.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L217.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1L32 320c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM352 416l64 0c17.7 0 32-14.3 32-32l0-256c0-17.7-14.3-32-32-32l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64 0c53 0 96 43 96 96l0 256c0 53-43 96-96 96l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32z"/></svg></i>
                Sign In
            </a>

            <a href="{{ route('signin-signup') }}" class="btn teacher">
                <i aria-hidden="true"><svg viewBox="0 0 640 512"><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312V248H440c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V136c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H552v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg></i>
                Sign Up
            </a>

        </div>

    </div>

</section>

<!-- ================= FEATURES ================= -->
<section class="features reveal">

    <h3>Platform Features</h3>

    <p class="section-desc">
        A comprehensive learning solution designed to enhance mathematics education
    </p>

    <div class="feature-grid">

        <div class="feature-card reveal">
            <i aria-hidden="true"><svg viewBox="0 0 576 512"><path d="M249.6 471.5c10.8 3.8 22.4-4.1 22.4-15.5V78.6c0-4.2-1.6-8.4-5-11C247.4 52 202.4 32 144 32C93.5 32 46.3 45.3 18.1 56.1C6.8 60.5 0 71.7 0 83.8V454.1c0 11.9 12.8 20.2 24.1 16.5C55.6 460.1 105.5 448 144 448c33.9 0 79 14 105.6 23.5zm76.8 0C353 462 398.1 448 432 448c38.5 0 88.4 12.1 119.9 22.6c11.3 3.8 24.1-4.6 24.1-16.5V83.8c0-12.1-6.8-23.3-18.1-27.6C529.7 45.3 482.5 32 432 32c-58.4 0-103.4 20-123 35.6c-3.3 2.6-5 6.8-5 11V456c0 11.4 11.7 19.3 22.4 15.5z"/></svg></i>

            <h4>Interactive Modules</h4>

            <p>
                Structured learning content covering Number Sense,
                Algebra, Geometry, and Statistics
            </p>
        </div>

        <div class="feature-card reveal">
            <i aria-hidden="true"><svg viewBox="0 0 640 512"><path d="M208 352c114.9 0 208-78.8 208-176S322.9 0 208 0S0 78.8 0 176c0 38.6 14.7 74.3 39.6 103.4c-3.5 9.4-8.7 17.7-14.2 24.7c-4.8 6.2-9.7 11-13.3 14.3c-1.8 1.6-3.3 2.9-4.3 3.7c-.5 .4-.9 .7-1.1 .8l-.2 .2 0 0 0 0C1 327.2-1.4 334.4 .8 340.9S9.1 352 16 352c21.8 0 43.8-5.6 62.1-12.5c9.2-3.5 17.8-7.4 25.3-11.4C134.1 343.3 169.8 352 208 352zM448 176c0 112.3-99.1 196.9-216.5 207C255.8 457.4 336.4 512 432 512c38.2 0 73.9-8.7 104.7-23.9c7.5 4 16 7.9 25.2 11.4c18.3 6.9 40.3 12.5 62.1 12.5c6.9 0 13.1-4.5 15.2-11.1c2.1-6.6-.2-13.8-5.8-17.9l0 0 0 0-.2-.2c-.2-.2-.6-.4-1.1-.8c-1-.8-2.5-2-4.3-3.7c-3.6-3.3-8.5-8.1-13.3-14.3c-5.5-7-10.7-15.4-14.2-24.7c24.9-29 39.6-64.7 39.6-103.4c0-92.8-84.9-168.9-192.6-175.5c.4 5.1 .6 10.3 .6 15.5z"/></svg></i>

            <h4>AI Chatbot Support</h4>

            <p>
                24/7 intelligent assistance for math questions
                and problem-solving guidance
            </p>
        </div>

        <div class="feature-card reveal">
            <i aria-hidden="true"><svg viewBox="0 0 384 512"><path d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V160H256c-17.7 0-32-14.3-32-32V0H64zM256 0V128H384L256 0zM112 256H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/></svg></i>

            <h4>Assessments & Quizzes</h4>

            <p>
                Interactive tests with instant feedback
                and downloadable materials
            </p>
        </div>

        <div class="feature-card reveal">
            <i aria-hidden="true"><svg viewBox="0 0 512 512"><path d="M32 32c17.7 0 32 14.3 32 32V400c0 8.8 7.2 16 16 16H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H80c-44.2 0-80-35.8-80-80V64C0 46.3 14.3 32 32 32zM160 224c17.7 0 32 14.3 32 32v64c0 17.7-14.3 32-32 32s-32-14.3-32-32V256c0-17.7 14.3-32 32-32zm128-64V320c0 17.7-14.3 32-32 32s-32-14.3-32-32V160c0-17.7 14.3-32 32-32s32 14.3 32 32zm64 32c17.7 0 32 14.3 32 32v96c0 17.7-14.3 32-32 32s-32-14.3-32-32V224c0-17.7 14.3-32 32-32zM480 96V320c0 17.7-14.3 32-32 32s-32-14.3-32-32V96c0-17.7 14.3-32 32-32s32 14.3 32 32z"/></svg></i>

            <h4>Progress Tracking</h4>

            <p>
                Comprehensive analytics for students,
                teachers, and administrators
            </p>
        </div>

        <div class="feature-card reveal">
            <i aria-hidden="true"><svg viewBox="0 0 640 512"><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg></i>

            <h4>Teacher Dashboard</h4>

            <p>
                Monitor student performance and provide
                personalized feedback
            </p>
        </div>

        <div class="feature-card reveal">
            <i aria-hidden="true"><svg viewBox="0 0 512 512"><path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32V274.7l-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L288 274.7V32zM64 352c-35.3 0-64 28.7-64 64v32c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V416c0-35.3-28.7-64-64-64H346.5l-45.3 45.3c-25 25-65.5 25-90.5 0L165.5 352H64zm368 56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/></svg></i>

            <h4>Offline Access</h4>

            <p>
                Download modules and assessments
                for learning without internet
            </p>
        </div>

    </div>

</section>

<!-- ================= TOPICS ================= -->
<section class="topics reveal">

    <h2>Mathematics Topics</h2>

    <p class="subtitle">
        Comprehensive coverage of Junior High School mathematics fundamentals
    </p>

    <div class="topics-grid">

        <div class="topic-card reveal">
            <span>1</span>
            <p>Sequences and Series</p>
        </div>

        <div class="topic-card reveal">
            <span>2</span>
            <p>Polynomials and Polynomial Equations</p>
        </div>

        <div class="topic-card reveal">
            <span>3</span>
            <p>Advanced Equations and Functions</p>
        </div>

    </div>

</section>

</main>

<!-- ================= FOOTER ================= -->
<footer class="footer">

    <p>
        © 2026 Math Learning Assistant - Bubog National High School
    </p>

    <p class="footer-sub">
        Empowering students through interactive mathematics education
    </p>

</footer>

<!-- ================= CSRF ================= -->
<script>
    window.Laravel = {
        csrfToken: '{{ csrf_token() }}'
    };
</script>

</body>
</html>

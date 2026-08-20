<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>MATHsaLOVE</title>

    <!-- ================= GOOGLE FONT ================= -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- ================= FONT AWESOME ================= -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- ================= CSS / JS ================= -->
    @vite([
        'resources/css/homepage.css',
        'resources/js/homepage.js'
    ])

</head>

<body>

<!-- ================= HERO ================= -->
<section class="hero">

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
                <i class="fa-solid fa-sign-in-alt"></i>
                Sign In
            </a>

            <a href="{{ route('signin-signup') }}" class="btn teacher">
                <i class="fa-solid fa-user-plus"></i>
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
            <i class="fa-solid fa-book-open"></i>

            <h4>Interactive Modules</h4>

            <p>
                Structured learning content covering Number Sense,
                Algebra, Geometry, and Statistics
            </p>
        </div>

        <div class="feature-card reveal">
            <i class="fa-solid fa-comments"></i>

            <h4>AI Chatbot Support</h4>

            <p>
                24/7 intelligent assistance for math questions
                and problem-solving guidance
            </p>
        </div>

        <div class="feature-card reveal">
            <i class="fa-solid fa-file-lines"></i>

            <h4>Assessments & Quizzes</h4>

            <p>
                Interactive tests with instant feedback
                and downloadable materials
            </p>
        </div>

        <div class="feature-card reveal">
            <i class="fa-solid fa-chart-column"></i>

            <h4>Progress Tracking</h4>

            <p>
                Comprehensive analytics for students,
                teachers, and administrators
            </p>
        </div>

        <div class="feature-card reveal">
            <i class="fa-solid fa-users"></i>

            <h4>Teacher Dashboard</h4>

            <p>
                Monitor student performance and provide
                personalized feedback
            </p>
        </div>

        <div class="feature-card reveal">
            <i class="fa-solid fa-download"></i>

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

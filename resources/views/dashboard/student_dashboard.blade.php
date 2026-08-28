<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <!-- CSRF -->
    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>
        Math Learning Assistant - Student Dashboard
    </title>

    <!-- GOOGLE FONT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    {{-- SweetAlert2 (JS + CSS) is bundled through Vite in student_dashboard.js. --}}

    <!-- MODULES URL -->
    <meta name="modules-url"
          content="{{ route('student.modules') }}">

    <!-- ================= MATHJAX ================= -->

    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)']],
                displayMath: [['\\[', '\\]']],
                processEscapes: true
            },
            svg: {
                fontCache: 'global'
            }
        };
    </script>

    <script
        id="MathJax-script"
        async
        src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"
        integrity="sha384-KKWa9jJ1MZvssLeOoXG6FiOAZfAgmzsIIfw8BXwI9+kYm0lPCbC6yTQPBC00F1/L"
        crossorigin="anonymous"
        referrerpolicy="no-referrer">
    </script>

    <script>
        // Expose the authenticated user to the frontend.
        window.__USER__ = {
            id:   "{{ auth()->user()->id }}",
            name: "{{ auth()->user()->name }}",
            role: "{{ auth()->user()->role ?? 'student' }}",
        };
    </script>

    <!-- VITE -->
    @vite([
        'resources/css/dashboard/student_dashboard.css',
        'resources/css/dashboard/chatbot.css',
        'resources/css/dashboard/math-panel.css',
        'resources/js/polling.js',
        'resources/js/nav-progress.js',
        'resources/js/dashboard/student_dashboard.js',
        'resources/js/dashboard/chatbot.js',
        'resources/js/dashboard/math-panel.js',
    ])

</head>
<body>

<div class="app-shell">

    <!-- ================================
         DESKTOP SIDEBAR
         ================================ -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="logo-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
            </div>
            <span class="brand-name">Math Learning</span>
        </div>

        <nav class="sidebar-nav">
            <button class="sidebar-item active" data-page="home">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Home
            </button>
            {{-- MODULES: Laravel named route --}}
            <button class="sidebar-item" onclick="window.location.href='{{ route('student.modules') }}'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Modules
            </button>
            <button class="sidebar-item" data-page="progress">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                Progress
            </button>
            <button class="sidebar-item" data-page="feedback" style="position:relative">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Feedback
                <span id="feedback-unread-badge" style="display:none;position:absolute;top:6px;left:26px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;border-radius:999px;min-width:16px;height:16px;line-height:16px;text-align:center;padding:0 4px;"></span>
            </button>
            <button class="sidebar-item" data-page="profile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Profile
            </button>
        </nav>

        <div class="sidebar-fab">
            <button class="sidebar-fab-btn" id="sidebar-chat-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                AI Chat
            </button>
        </div>

        <div class="sidebar-logout">
            <button class="sidebar-logout-btn" onclick="confirmLogout()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </button>
        </div>
    </aside>

    <!-- ================================
         MAIN WRAPPER
         ================================ -->
    <div class="main-wrapper">

        <!-- Mobile / Tablet Header -->
        <header class="header">
            <div class="logo-section">
                <div class="logo-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <span class="brand-name">Math Learning Assistant</span>
            </div>
            <button class="logout-btn" onclick="confirmLogout()">Logout</button>
        </header>

        <main class="main-content">

            <!-- ===== HOME PAGE ===== -->
            <div class="page active" id="page-home">
                <div class="hero-section">
                    <h1 class="welcome-title">Welcome, Student! 👋</h1>
                    <p class="welcome-subtitle">Continue your mathematics learning journey</p>
                </div>

                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card" onclick="navigate('progress')">
                            <div class="metric-header">
                                <span class="metric-label">Overall Progress</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="home-overall-progress">0%</div>
                            <div class="metric-sub">across all modules</div>
                        </div>
                        <div class="metric-card" onclick="navigate('modules')">
                            <div class="metric-header">
                                <span class="metric-label">Topics Done</span>
                                <div class="icon-container orange-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.21 13.89L7 23L12 20L17 23L15.79 13.88"/><circle cx="12" cy="8" r="7"/><circle cx="12" cy="8" r="3"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="home-topics-done">0/12</div>
                            <div class="metric-sub">keep going!</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Current Streak</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="home-streak">0</div>
                            <div class="metric-sub">days streak</div>
                        </div>
                    </div>
                </div>

                <section class="modules-container">
                    <div class="section-label">Learning Modules</div>
                    <div class="section-sub">Track your progress across all topics</div>

                    <div class="module-item">
                        <div class="module-title-row">
                            <span class="status-icon" id="home-mod1-icon">—</span>
                            <span class="module-name">Sequences and Series</span>
                            <span class="percentage blue" id="home-mod1-pct">0%</span>
                        </div>
                        <div class="progress-bar-bg"><div class="progress-fill blue" id="home-mod1-fill" style="width:0%"></div></div>
                        <button class="view-topics-btn" onclick="navigate('modules', 1)">View Topics</button>
                    </div>

                    <div class="module-item">
                        <div class="module-title-row">
                            <span class="status-icon" id="home-mod2-icon">—</span>
                            <span class="module-name">Polynomials and Polynomial Equations</span>
                            <span class="percentage blue" id="home-mod2-pct">0%</span>
                        </div>
                        <div class="progress-bar-bg"><div class="progress-fill blue" id="home-mod2-fill" style="width:0%"></div></div>
                        <button class="view-topics-btn" onclick="navigate('modules', 2)">View Topics</button>
                    </div>

                    <div class="module-item">
                        <div class="module-title-row">
                            <span class="status-icon" id="home-mod3-icon">—</span>
                            <span class="module-name">Advanced Equations and Functions</span>
                            <span class="percentage blue" id="home-mod3-pct">0%</span>
                        </div>
                        <div class="progress-bar-bg"><div class="progress-fill blue" id="home-mod3-fill" style="width:0%"></div></div>
                        <button class="view-topics-btn" onclick="navigate('modules', 3)">View Topics</button>
                    </div>
                </section>

                <div class="bottom-grid">
                    <div class="action-card">
                        <div class="action-icon-wrap blue-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <div class="action-content">
                            <h3>AI Chatbot</h3>
                            <p>Get instant help with your math questions</p>
                            <button class="primary-btn" id="start-chat-btn">Start Chat</button>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon-wrap green-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </div>
                        <div class="action-content">
                            <h3>Offline Materials</h3>
                            <p>Download assessments to practice offline</p>
                            <button class="outline-btn" onclick="navigate('downloads')">View Downloads</button>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon-wrap blue-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                        </div>
                        <div class="action-content">
                            <h3>Summative Test</h3>
                            <p>Test your knowledge with an interactive summative assessment</p>
                            <button class="primary-btn" onclick="navigate('summative'); setTimeout(() => { document.getElementById('initial-cta').style.display='none'; document.getElementById('quiz-start-screen').style.display='block'; }, 200);">Start Summative Test</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== PROGRESS PAGE ===== -->
            <div class="page" id="page-progress">
                <div class="hero-section">
                    <h1 class="welcome-title">My Progress</h1>
                    <p class="welcome-subtitle">See how far you've come</p>
                </div>

                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Overall</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="progress-overall">0%</div>
                            <div class="metric-sub">all modules</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Topics Done</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="progress-topics-done">0/12</div>
                            <div class="metric-sub">total topics</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Quiz Attempts</span>
                                <div class="icon-container orange-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.21 13.89L7 23L12 20L17 23L15.79 13.88"/><circle cx="12" cy="8" r="7"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="progress-attempts">0</div>
                            <div class="metric-sub">pre &amp; post-tests taken</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Avg. Pre-Test</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="progress-avg-pre">—</div>
                            <div class="metric-sub">before studying</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Improvement</span>
                                <div class="icon-container purple-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="progress-improvement">—</div>
                            <div class="metric-sub">post-test vs pre-test</div>
                        </div>
                    </div>
                </div>

                <section class="modules-container">
                    <div class="section-label">Module Progress</div>
                    <div class="section-sub">Detailed breakdown by module</div>

                    <div class="progress-row">
                        <div class="progress-label"><span>Sequences and Series</span><span id="progress-mod1-pct">0%</span></div>
                        <div class="progress-bar"><div class="progress-fill-bar" id="progress-mod1-fill" style="width:0%; background:var(--blue)"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-label"><span>Polynomials and Polynomial Equations</span><span id="progress-mod2-pct">0%</span></div>
                        <div class="progress-bar"><div class="progress-fill-bar" id="progress-mod2-fill" style="width:0%; background:var(--orange)"></div></div>
                    </div>
                    <div class="progress-row" style="margin-bottom:0">
                        <div class="progress-label"><span>Advanced Equations and Functions</span><span id="progress-mod3-pct">0%</span></div>
                        <div class="progress-bar"><div class="progress-fill-bar" id="progress-mod3-fill" style="width:0%; background:var(--purple)"></div></div>
                    </div>
                </section>

                <section class="modules-container">
                    <div class="section-label">Recent Activity</div>
                    <div class="section-sub">Your latest learning events</div>
                    <div class="empty-state" id="recent-activity-empty">
                        <div class="empty-icon">📋</div>
                        <h4>No activity yet</h4>
                        <p>Start a module to track your progress here.</p>
                    </div>
                    <div id="recent-activity-list" style="display:none"></div>
                </section>
            </div>

            <!-- ===== FEEDBACK PAGE ===== -->
            <div class="page" id="page-feedback">
                <div class="hero-section">
                    <h1 class="welcome-title">Feedback</h1>
                    <p class="welcome-subtitle">Messages from your teacher</p>
                </div>

                <section class="modules-container">
                    <div class="empty-state" id="feedback-empty">
                        <div class="empty-icon">💬</div>
                        <h4>No feedback yet</h4>
                        <p>Your teacher's feedback will appear here.</p>
                    </div>
                    <div id="feedback-list" style="display:none"></div>
                </section>
            </div>

            <!-- ===== PROFILE PAGE ===== -->
            <div class="page" id="page-profile">
                <div class="hero-section">
                    <h1 class="welcome-title">My Profile</h1>
                    <p class="welcome-subtitle">Manage your account and preferences</p>
                </div>

                <div class="profile-card">
                    <div class="profile-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}</div>
                    <div class="profile-name">{{ auth()->user()->name ?? 'Student' }}</div>
                    <div class="profile-email">{{ auth()->user()->email ?? 'student@mathlearning.edu' }}</div>
                    <span class="profile-badge">Student</span>
                </div>

                <div class="settings-section">
                    <h3>Account Information</h3>
                    <p class="desc">Your registered account details</p>
                    <div class="field-row">
                        <label>Full Name</label>
                        <input type="text" value="{{ auth()->user()->name ?? '' }}" placeholder="Your full name" readonly>
                    </div>
                    <div class="field-row">
                        <label>Email Address</label>
                        <input type="email" value="{{ auth()->user()->email ?? '' }}" placeholder="Your email" readonly>
                    </div>
                    <div class="field-row">
                        <label>Student ID</label>
                        <input type="text" value="{{ auth()->user()->student_id ?? '' }}" placeholder="No student ID on file" readonly>
                    </div>
                    <div class="field-row">
                        <label>Section / Class</label>
                        <input type="text" value="{{ auth()->user()->section?->name ?? '' }}" placeholder="No section selected yet" readonly>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>Change Password</h3>
                    <p class="desc">Keep your account secure</p>
                    <div class="field-row">
                        <label for="pw-current">Current Password</label>
                        <input type="password" id="pw-current" placeholder="••••••••" autocomplete="current-password">
                    </div>
                    <div class="field-row">
                        <label for="pw-new">New Password</label>
                        <input type="password" id="pw-new" placeholder="••••••••" autocomplete="new-password">
                    </div>
                    <div class="field-row">
                        <label for="pw-confirm">Confirm New Password</label>
                        <input type="password" id="pw-confirm" placeholder="••••••••" autocomplete="new-password">
                    </div>
                    <div class="save-row">
                        <button class="btn-cancel" onclick="clearPasswordForm()">Cancel</button>
                        <button class="btn-save" onclick="updatePassword()">Update Password</button>
                    </div>
                </div>
            </div>

            <!-- ===== DOWNLOADS PAGE ===== -->
            <div class="page" id="page-downloads">
                <div class="hero-section">
                    <h1 class="welcome-title">Offline Materials 📥</h1>
                    <p class="welcome-subtitle">Download assessments and worksheets to practice offline</p>
                </div>

                <section class="modules-container" id="section-mod1">
                    <div class="section-label">Module 1 — Sequences and Series</div>
                    <div class="section-sub">Practice worksheets and assessment sheets</div>

                    <div class="download-item">
                        <div class="download-icon green-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Arithmetic Sequence</span>
                            <span class="download-meta">PDF · 472 KB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Arithmetic Sequence.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>
                    
                    <div class="download-item">
                        <div class="download-icon green-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Geometric Sequence</span>
                            <span class="download-meta">PDF · 532 KB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Geometric Sequence.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>

                    <div class="download-item">
                        <div class="download-icon green-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Harmonic Sequence</span>
                            <span class="download-meta">PDF · 89 KB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Harmonic Sequence.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>

                    <div class="download-item">
                        <div class="download-icon green-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Fibonacci Sequence</span>
                            <span class="download-meta">PDF · 70 KB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Fibonacci Sequence.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>

                    <div class="download-item">
                        <div class="download-icon green-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Finite and Infinite Sequence</span>
                            <span class="download-meta">PDF · 512 KB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Finite and Infinite Sequence.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>
                </section>

                <section class="modules-container" id="section-mod2">
                    <div class="section-label">Module 2 — Polynomials and Polynomial Equations</div>
                    <div class="section-sub">Practice worksheets and assessment sheets</div>

                    <div class="download-item">
                        <div class="download-icon orange-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Division of Polynomials</span>
                            <span class="download-meta">PDF · 514 KB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Division of Polynomials.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>

                    <div class="download-item">
                        <div class="download-icon orange-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">The Remainder Theorem and Factor Theorem</span>
                            <span class="download-meta">PDF · 577 KB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('The Remainder and Factor Theorem.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>

                    <div class="download-item">
                        <div class="download-icon orange-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Polynomial Equations</span>
                            <span class="download-meta">PDF · 661 KB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Polynomial Equation.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>
                </section>

                <section class="modules-container" id="section-mod3">
                    <div class="section-label">Module 3 — Advanced Equations and Functions</div>
                    <div class="section-sub">Practice worksheets and assessment sheets</div>

                    <div class="download-item">
                        <div class="download-icon purple-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Rational Equations</span>
                            <span class="download-meta">PDF · 1.1 MB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Rational Functions.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>

                    <div class="download-item">
                        <div class="download-icon purple-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Radical Equations</span>
                            <span class="download-meta">PDF · 3.9 MB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Radical Equations.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>

                    <div class="download-item">
                        <div class="download-icon purple-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Exponential Functions</span>
                            <span class="download-meta">PDF · 1.5 MB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Exponential Functions.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>

                    <div class="download-item">
                        <div class="download-icon purple-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="download-info">
                            <span class="download-name">Logarithmic Functions</span>
                            <span class="download-meta">PDF · 1.3 MB</span>
                        </div>
                        <button class="dl-btn" onclick="handleDownload('Logarithmic Functions.pdf')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>
                </section>
            </div>

            <!-- ===== SUMMATIVE TEST PAGE ===== -->
            <div class="page" id="page-summative">
                <div class="hero-section">
                    <h1 class="welcome-title">Summative Test 📋</h1>
                    <p class="welcome-subtitle">Answer all questions carefully. You can review before submitting.</p>
                </div>

                <!-- START TEST BUTTON (Initial CTA) -->
                <div id="initial-cta" style="text-align:center; margin-bottom:20px;">
                    <button class="primary-btn" style="max-width:320px; margin:0 auto; padding:14px; font-size:15px;" onclick="document.getElementById('initial-cta').style.display='none'; document.getElementById('quiz-start-screen').style.display='block';">Start Summative Test →</button>
                </div>

                <!-- LOCK STATUS INDICATOR -->
                <div id="summative-lock-notice" style="display:none; margin-bottom:20px;">
                    <section class="modules-container" style="background: #fef3c7; border: 1px solid #fcd34d; text-align: center; padding: 20px; opacity: 1; animation: none;">
                        <div style="font-size: 32px; margin-bottom: 8px;">🔒</div>
                        <div class="section-label" style="color: #b45309; margin-bottom: 4px;">Test Not Yet Available</div>
                        <div style="font-size: 13px; color: #92400e; margin-bottom: 16px;">Complete all 3 modules first to unlock this comprehensive test.</div>
                        <div id="lock-progress-display" style="text-align: left; margin-top: 12px;"></div>
                    </section>
                </div>

                <div id="quiz-start-screen" style="display:none;">
                    <section class="modules-container">
                        <div class="section-label">Test Instructions</div>
                        <div class="section-sub">Read before you begin</div>
                        <div class="download-item" style="border:none; padding:0; margin-bottom:10px;">
                            <div class="download-icon blue-theme">📖</div>
                            <div class="download-info"><span class="download-name">This test covers all 3 modules.</span><span class="download-meta">Sequences · Polynomials · Advanced Equations</span></div>
                        </div>
                        <div class="download-item" style="border:none; padding:0; margin-bottom:10px;">
                            <div class="download-icon orange-theme">❓</div>
                            <div class="download-info"><span class="download-name">10 multiple choice questions</span><span class="download-meta">Choose the best answer for each item</span></div>
                        </div>
                        <div class="download-item" style="border:none; padding:0; margin-bottom:0;">
                            <div class="download-icon green-theme">✅</div>
                            <div class="download-info"><span class="download-name">Review your answers before submitting</span><span class="download-meta">You can go back and change answers anytime</span></div>
                        </div>
                    </section>
                    <button class="primary-btn" id="start-summative-btn" style="max-width:320px; margin:0 auto; display:block; padding:14px; font-size:15px;" onclick="startQuiz()">Begin Summative Test →</button>
                </div>

                <div id="quiz-question-screen" style="display:none;">
                    <div class="modules-container" id="quiz-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                            <span class="section-label" id="quiz-q-label">Question 1 of 10</span>
                            <span class="profile-badge" id="quiz-score-badge">Score: 0</span>
                        </div>
                        <div style="background:var(--border); border-radius:99px; height:6px; margin-bottom:20px; overflow:hidden;">
                            <div id="quiz-progress-bar" style="height:100%; background:var(--blue); border-radius:99px; width:10%; transition:width 0.4s ease;"></div>
                        </div>
                        <p id="quiz-question-text" style="font-size:15px; font-weight:700; color:var(--text); line-height:1.5; margin-bottom:20px;"></p>
                        <div id="quiz-choices" style="display:flex; flex-direction:column; gap:10px;"></div>
                        <div style="display:flex; justify-content:flex-end; margin-top:20px; gap:10px;">
                            <button class="outline-btn" id="quiz-prev-btn" style="max-width:120px;" onclick="quizPrev()">← Back</button>
                            <button class="primary-btn" id="quiz-next-btn" style="max-width:160px;" onclick="quizNext()">Next →</button>
                        </div>
                    </div>
                </div>

                <div id="quiz-result-screen" style="display:none; text-align:center;">
                    <section class="modules-container">
                        <div id="quiz-result-emoji" style="font-size:56px; margin-bottom:12px;">🎉</div>
                        <div class="section-label" id="quiz-result-title">Test Complete!</div>
                        <div class="section-sub" id="quiz-result-sub">Here's how you did</div>
                        <div style="font-size:52px; font-weight:800; color:var(--blue); letter-spacing:-2px; margin:16px 0;" id="quiz-result-score">8/10</div>
                        <div style="font-size:14px; color:var(--text-3); margin-bottom:24px;" id="quiz-result-msg"></div>
                        <button class="primary-btn" style="max-width:240px; margin:0 auto;" onclick="retakeQuiz()">Retake Test</button>
                    </section>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ================================
     BOTTOM NAV (mobile/tablet only)
     ================================ -->
<nav class="bottom-nav">
    <button class="nav-item active" data-page="home">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Home</span>
        <div class="nav-dot"></div>
    </button>
    {{-- MODULES: Laravel named route --}}
    <button class="nav-item" onclick="window.location.href='{{ route('student.modules') }}'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <span>Modules</span>
        <div class="nav-dot"></div>
    </button>
    <div class="fab">
        <button class="fab-btn" id="fab-chat" aria-label="Open Math AI Assistant" aria-expanded="false" aria-controls="ai-chat-window">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 8V4H8"></path>
                <rect x="4" y="8" width="16" height="12" rx="2"></rect>
                <path d="M2 14h2"></path>
                <path d="M20 14h2"></path>
                <path d="M9 13v2"></path>
                <path d="M15 13v2"></path>
            </svg>
        </button>
        <span class="fab-label">AI Chat</span>
    </div>
    <button class="nav-item" data-page="progress">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        <span>Progress</span>
        <div class="nav-dot"></div>
    </button>
    <button class="nav-item" data-page="feedback" style="position:relative">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span>Feedback</span>
        <span id="feedback-unread-badge-mobile" style="display:none;position:absolute;top:2px;right:14px;background:#ef4444;color:#fff;font-size:9px;font-weight:700;border-radius:999px;min-width:14px;height:14px;line-height:14px;text-align:center;padding:0 3px;"></span>
        <div class="nav-dot"></div>
    </button>
    <button class="nav-item" data-page="profile">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>Profile</span>
        <div class="nav-dot"></div>
    </button>
</nav>

{{-- SweetAlert2 JS is bundled through Vite (student_dashboard.js imports it
     directly), so no CDN script here. --}}

<form id="logout-form" method="POST" action="{{ route('student.logout') }}" style="display:none;">
    @csrf
</form>

{{-- window.handleDownload is defined in resources/js/dashboard/student_dashboard.js --}}

    {{-- 1. I-include ang chatbot HTML --}}
    @include('dashboard.chatbot')

    {{-- 2. Siguraduhin na may Global Fallback para sa Token --}}
    <script>
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}'
        };
    </script>

    {{-- chatbot.js and math-panel.js are already loaded via the @vite([...]) block in <head>. --}}

<script>

function renderMath(element) {

    if (window.MathJax) {

        MathJax.typesetPromise([element])
            .catch(function (err) {
                console.log(err.message);
            });

    }

}

</script>

    
</body>
</html>
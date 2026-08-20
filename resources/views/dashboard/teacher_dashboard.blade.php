<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <title>Math Learning Assistant - Teacher Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- PDF/Excel export (Reports tab) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/4.2.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- Expose environment variables to frontend -->
    <script>
        window.__ENV__ = {
            SUPABASE_URL:      "{{ config('services.supabase.url') }}",
            SUPABASE_ANON_KEY: "{{ config('services.supabase.anon_key') }}",
        };

        window.__USER__ = {
            id: {{ auth()->user()->id }},
            name: "{{ auth()->user()->name }}",
            role: "{{ auth()->user()->role }}",
        };

        // Provide safe Supabase init helper (used by module/teacher JS)
        window.getSupabaseClient = function(timeout = 3000) {
            return new Promise((resolve, reject) => {
                if (window.supabaseClient) return resolve(window.supabaseClient);
                const interval = 75; let waited = 0;
                const id = setInterval(() => {
                    if (typeof supabase !== 'undefined' && supabase && typeof supabase.createClient === 'function') {
                        try {
                            window.supabaseClient = supabase.createClient(window.__ENV__.SUPABASE_URL, window.__ENV__.SUPABASE_ANON_KEY);
                            clearInterval(id);
                            return resolve(window.supabaseClient);
                        } catch (e) {
                            clearInterval(id);
                            return reject(e);
                        }
                    }
                    waited += interval;
                    if (waited >= timeout) { clearInterval(id); return reject(new Error('Supabase client not initialized (supabase.js may have failed to load)')); }
                }, interval);
            });
        };

    </script>

    @vite([
        'resources/css/dashboard/teacher_dashboard.css',
        'resources/js/dashboard/teacher_dashboard.js'
    ])
</head>
<body>
<div class="app-shell">

    <!-- DESKTOP SIDEBAR -->
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Home
            </button>
            <button class="sidebar-item" data-page="students">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Students
            </button>
            <button class="sidebar-item" data-page="progress">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                    <polyline points="17 6 23 6 23 12"/>
                </svg>
                Progress
            </button>
            <button class="sidebar-item" data-page="reports">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                Reports
            </button>
            <button class="sidebar-item" data-page="modules">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                Modules
            </button>
            <!-- QUIZ NAV ITEM -->
            <button class="sidebar-item" data-page="quiz">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                Quiz Gen
            </button>
            <button class="sidebar-item" data-page="profile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Profile
            </button>
        </nav>

        <div class="sidebar-logout">
            <button class="sidebar-logout-btn" id="logout-btn-desktop">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </button>
        </div>
    </aside>

    <form id="logout-form" method="POST" action="{{ route('teacher.logout') }}" style="display:none;">
        @csrf
    </form>

    <div class="main-wrapper">

        <!-- MOBILE HEADER -->
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
            <button class="logout-btn" id="logout-btn-mobile">Logout</button>
        </header>

        <main class="main-content">

            <!-- HOME PAGE -->
            <div class="page active" id="page-home">
                <div class="hero-section">
                    <h1 class="welcome-title">Welcome, Teacher! 👋</h1>
                    <p class="welcome-subtitle">Monitor and guide your students' learning journey</p>
                </div>

                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card" onclick="navigate('students')">
                            <div class="metric-header">
                                <span class="metric-label">Total Students</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="metric-value" id="m-total">0</div>
                            <div class="metric-sub">Active learners</div>
                        </div>

                        <div class="metric-card" onclick="navigate('progress')">
                            <div class="metric-header">
                                <span class="metric-label">Avg. Progress</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="metric-value" id="m-avg">0%</div>
                            <div class="metric-sub">Across all modules</div>
                        </div>

                        <div class="metric-card" onclick="navigate('reports')">
                            <div class="metric-header">
                                <span class="metric-label">Pending Feedback</span>
                                <div class="icon-container orange-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="metric-value" id="m-pending">0</div>
                            <div class="metric-sub">Awaiting review</div>
                        </div>

                        <div class="metric-card" onclick="navigate('quiz')">
                            <div class="metric-header">
                                <span class="metric-label">Quizzes</span>
                                <div class="icon-container purple-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="metric-value" id="m-quizzes">0</div>
                            <div class="metric-sub">Generated quizzes</div>
                        </div>
                    </div>
                </div>

                <section class="modules-container">
                    <div class="section-label">Recent Student Activity</div>
                    <div class="section-sub">Monitor your students' progress</div>
                    <div id="home-student-list">
                        <div class="empty-state">
                            <div class="empty-icon">👩‍🎓</div>
                            <h4>No students yet</h4>
                            <p>Students will appear here once they enroll in your class.</p>
                        </div>
                    </div>
                    <button class="view-topics-btn" onclick="navigate('students')">View All Students</button>
                </section>

                <div class="bottom-grid">
                    <div class="action-card">
                        <div class="action-icon-wrap blue-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                        </div>
                        <div class="action-content">
                            <h3>Send Feedback</h3>
                            <p>Give personalized recommendations to students</p>
                            <button class="primary-btn" onclick="navigate('students')">Go to Students</button>
                        </div>
                    </div>

                    <div class="action-card">
                        <div class="action-icon-wrap orange-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </div>
                        <div class="action-content">
                            <h3>Generate Reports</h3>
                            <p>Create detailed student performance reports</p>
                            <button class="primary-btn" onclick="navigate('reports')">View Reports</button>
                        </div>
                    </div>

                    <div class="action-card">
                        <div class="action-icon-wrap purple-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <div class="action-content">
                            <h3>Generate Quiz</h3>
                            <p>AI-powered pre-test & post-test generation</p>
                            <button class="primary-btn" onclick="navigate('quiz')">Create Quiz</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STUDENTS PAGE -->
            <div class="page" id="page-students">
                <div class="hero-section">
                    <h1 class="welcome-title">Students</h1>
                    <p class="welcome-subtitle">View, search, and send feedback to your students</p>
                </div>

                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Total</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="s-total">0</div>
                            <div class="metric-sub">enrolled students</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Avg. Progress</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="s-avg">0%</div>
                            <div class="metric-sub">class average</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Need Help</span>
                                <div class="icon-container orange-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="s-help">0</div>
                            <div class="metric-sub">require attention</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Excellent</span>
                                <div class="icon-container purple-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="s-excellent">0</div>
                            <div class="metric-sub">top performers</div>
                        </div>
                    </div>
                </div>

                <div class="modules-container">
                    @if ($pendingStudents->count() > 0)
                    <div class="section-label">⚠️ Pending Student Approvals</div>
                    <div class="section-sub">{{ $pendingStudents->count() }} student(s) awaiting your approval &middot; <a href="{{ route('teacher.student-approvals') }}" style="color:#1e88e5;font-weight:600;">Manage all &rarr;</a></div>
                    <div class="pending-teachers-list" style="margin-bottom: 2rem;">
                        @foreach ($pendingStudents as $student)
                        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; margin-bottom: 0.75rem; border-radius: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; color: #78350f;">{{ $student->name }}</div>
                                <div style="font-size: 0.875rem; color: #92400e;">{{ $student->email }}</div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <form method="POST" action="{{ route('teacher.student.approve', $student->id) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('teacher.student.reject', $student->id) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;">Reject</button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="section-label">All Students</div>
                    <div class="section-sub">Search, filter, and manage your students</div>
                    <div class="toolbar">
                        <input type="text" class="search-input" id="student-search"
                               placeholder="🔍  Search by name…" oninput="filterStudents()"
                               maxlength="100" autocomplete="off">
                        <select class="filter-select" id="student-status-filter" onchange="filterStudents()">
                            <option value="">All Status</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Average">Average</option>
                            <option value="Needs Help">Needs Help</option>
                        </select>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>Last Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="students-tbody"></tbody>
                        </table>
                    </div>
                    <div class="pagination" id="student-pagination"></div>
                </div>
            </div>

            <!-- PROGRESS PAGE -->
            <div class="page" id="page-progress">
                <div class="hero-section">
                    <h1 class="welcome-title">Student Progress</h1>
                    <p class="welcome-subtitle">Track and review each student's learning journey</p>
                </div>

                <div class="chart-container">
                    <div class="chart-title">Performance Distribution</div>
                    <div class="chart-sub">Number of students per performance level</div>
                    <div id="progress-chart">
                        <div class="empty-state">
                            <div class="empty-icon">📊</div>
                            <h4>No data yet</h4>
                            <p>Charts will appear as students complete activities.</p>
                        </div>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-title">Subject Completion Rates</div>
                    <div class="chart-sub">How far your class has progressed in each module</div>
                    <div id="subject-progress">
                        <div class="empty-state">
                            <div class="empty-icon">📈</div>
                            <h4>No progress data yet</h4>
                            <p>Data appears as students complete modules.</p>
                        </div>
                    </div>
                </div>

                <div class="modules-container">
                    <div class="section-label">Individual Progress</div>
                    <div class="section-sub">How far each student has progressed</div>
                    <div id="progress-list">
                        <div class="empty-state">
                            <div class="empty-icon">📈</div>
                            <h4>No student data yet</h4>
                            <p>Progress will appear here as students complete activities.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REPORTS PAGE -->
            <div class="page" id="page-reports">
                <div class="hero-section">
                    <h1 class="welcome-title">Reports</h1>
                    <p class="welcome-subtitle">Generate and review detailed student performance reports</p>
                </div>

                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Students</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="r-total">0</div>
                            <div class="metric-sub">total enrolled</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Class Avg.</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="r-avg">0%</div>
                            <div class="metric-sub">average progress</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Feedbacks</span>
                                <div class="icon-container orange-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="r-feedback">0</div>
                            <div class="metric-sub">feedbacks sent</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Avg. Pre-Test</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="r-avg-pre">—</div>
                            <div class="metric-sub">class average</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Avg. Post-Test</span>
                                <div class="icon-container purple-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="r-avg-post">—</div>
                            <div class="metric-sub">class average</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header"><span class="metric-label">Improvement</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="r-improvement">—</div>
                            <div class="metric-sub">post-test vs pre-test</div>
                        </div>
                    </div>
                </div>

                <div class="modules-container">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                        <div>
                            <div class="section-label">Student Performance Reports by Section</div>
                            <div class="section-sub">Overview of students grouped by their registered sections</div>
                        </div>
                        <div style="display:flex;gap:8px">
                            <button class="success-btn" onclick="openAddSection()" style="display:flex;align-items:center;gap:6px;padding:10px 18px">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Add Section
                            </button>
                            <button class="primary-btn" onclick="showReportExportPicker()" style="display:flex;align-items:center;gap:6px;padding:10px 18px">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Export
                            </button>
                        </div>
                    </div>
                    <div id="sections-container"></div>
                </div>
            </div>

            <!-- MODULES PAGE -->
            <div class="page" id="page-modules">
                <div class="hero-section">
                    <h1 class="welcome-title">Modules</h1>
                    <p class="welcome-subtitle">Manage and assign learning modules for your students</p>
                </div>

                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Total Modules</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="mod-total">0</div>
                            <div class="metric-sub">available modules</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Published</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="mod-published">0</div>
                            <div class="metric-sub">active modules</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Draft</span>
                                <div class="icon-container orange-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="mod-draft">0</div>
                            <div class="metric-sub">in draft</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Avg. Completion</span>
                                <div class="icon-container purple-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="mod-completion">0%</div>
                            <div class="metric-sub">student completion</div>
                        </div>
                    </div>
                </div>

                <div class="modules-container">
                    <div class="section-label">All Modules</div>
                    <div class="section-sub">Browse, add, and manage your math learning modules</div>
                    <div class="toolbar">
                        <input type="text" class="search-input" id="module-search"
                               placeholder="🔍  Search modules…" oninput="filterModules()"
                               maxlength="100" autocomplete="off">
                        <select class="filter-select" id="module-topic-filter" onchange="filterModules()">
                            <option value="">All Topics</option>
                            <option value="Module 1: Sequences and Series">Arithmetic Sequence</option>
                            <option value="Module 1: Sequences and Series">Geometric Sequence</option>
                            <option value="Module 1: Sequences and Series">Harmonic Sequence</option>
                            <option value="Module 1: Sequences and Series">Fibonacci Sequence</option>
                            <option value="Module 1: Sequences and Series">Finite and Infinite Sequence</option>
                            <option value="Module 2: Polynomials">Division of Polynomials</option>
                            <option value="Module 2: Polynomials">The Remainder Theorem and Factor Theorem</option>
                            <option value="Module 2: Polynomials">Polynomial Equations</option>
                            <option value="Module 3: Advanced Equations">Rational Equations</option>
                            <option value="Module 3: Advanced Equations">Radical Equations</option>
                            <option value="Module 3: Advanced Equations">Exponential Functions</option>
                            <option value="Module 3: Advanced Equations">Logarithmic Functions</option>
                            
                        </select>
                        <button class="add-btn" onclick="openAddModule()">+ Add Module</button>
                    </div>
                    <div id="modules-grid" class="module-cards-grid">
                        <div class="empty-state">
                            <div class="empty-icon">📦</div>
                            <h4>No modules yet</h4>
                            <p>Click "Add Module" to create your first learning module.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 QUIZ GENERATION PAGE
                 ============================================================ -->
            <div class="page" id="page-quiz">
                <div class="hero-section">
                    <h1 class="welcome-title">Quiz Generator ✨</h1>
                    <p class="welcome-subtitle">AI-powered pre-test &amp; post-test generation for Math topics</p>
                </div>

                <!-- QUIZ METRICS -->
                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Total Quizzes</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="qz-total">0</div>
                            <div class="metric-sub">generated quizzes</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Pre-Tests</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="qz-pre">0</div>
                            <div class="metric-sub">pre-tests saved</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Post-Tests</span>
                                <div class="icon-container orange-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="qz-post">0</div>
                            <div class="metric-sub">post-tests saved</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Activities</span>
                                <div class="icon-container purple-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="qz-activities">5</div>
                            <div class="metric-sub">Activities</div>
                        </div>
                    </div>
                </div>

                <!-- QUIZ GENERATOR FORM -->
                <div class="modules-container quiz-gen-card">
                    <div class="section-label">Generate New Quiz</div>
                    <div class="section-sub">Configure topic, counts, and difficulty — AI will generate pre-test, activity, and post-test questions</div>

                    <div class="quiz-form-grid">
                        <!-- Topic Selector -->
                        <div class="field-row">
                            <label for="quiz-topic">Math Topic</label>
                            <select id="quiz-topic" onchange="updateActivityOptions()">
                                <option value="sequences">Module 1: Sequences and Series</option>
                                <option value="polynomials">Module 2: Polynomials</option>
                                <option value="advanced">Module 3: Advanced Equations</option>
                            </select>
                        </div>

                        <!-- Activity Selector (hardcoded, changes with topic) -->
                        <div class="field-row">
                            <label for="quiz-activity">Activity</label>
                            <select id="quiz-activity">
                                <option value="arithmetic_sequence">Activity 1: Arithmetic Sequence</option>
                                <option value="geometric_sequence">Activity 2: Geometric Sequence</option>
                                <option value="harmonic_sequence">Activity 3: Harmonic Sequence</option>
                                <option value="fibonacci_sequence">Activity 4: Fibonacci Sequence</option>
                                <option value="finite_infinite">Activity 5: Finite and Infinite Sequences</option>
                            </select>
                        </div>

                        <!-- Difficulty Selector -->
                        <div class="field-row">
                            <label for="quiz-difficulty">Difficulty Level</label>
                            <select id="quiz-difficulty">
                                <option value="easy">Easy - Foundational concepts</option>
                                <option value="medium" selected>Medium - Standard depth</option>
                                <option value="hard">Hard - Advanced application</option>
                            </select>
                        </div>
                    </div>

                    <!-- Item Count Inputs -->
                    <div style="margin-bottom:6px">
                        <div class="section-sub" style="margin-bottom:10px;display:flex;align-items:center;gap:6px">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 style="width:13px;height:13px;flex-shrink:0;color:#2563eb">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            Set how many questions to generate per section
                        </div>

                        <div class="quiz-count-row">

                            <!-- Pre-Test Count -->
                            <div class="quiz-count-field">
                                <label for="quiz-count-pre">
                                    📋 Pre-Test items
                                    <span class="quiz-count-badge">1 – 30</span>
                                </label>
                                <input type="number"
                                       id="quiz-count-pre"
                                       value="15"
                                       min="1"
                                       max="30"
                                       placeholder="15">
                            </div>

                            <!-- Activity Count -->
                            <div class="quiz-count-field">
                                <label for="quiz-count-act">
                                    ⚡ Activity items
                                    <span class="quiz-count-badge">1 – 10</span>
                                </label>
                                <input type="number"
                                       id="quiz-count-act"
                                       value="5"
                                       min="1"
                                       max="10"
                                       placeholder="5">
                            </div>

                            <!-- Post-Test Count -->
                            <div class="quiz-count-field">
                                <label for="quiz-count-post">
                                    ✅ Post-Test items
                                    <span class="quiz-count-badge">1 – 30</span>
                                </label>
                                <input type="number"
                                       id="quiz-count-post"
                                       value="15"
                                       min="1"
                                       max="30"
                                       placeholder="15">
                            </div>

                        </div>
                    </div>

                    <!-- Generate Button -->
                    <div class="quiz-action-row">
                        <button class="quiz-generate-btn" id="quiz-gen-btn" onclick="generateQuiz()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px;flex-shrink:0">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                            <span class="btn-text">Generate Quiz with AI</span>
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div class="quiz-loading-state" id="quiz-loading">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">
                            <div class="loading-title">Generating your quiz…</div>
                            <div class="loading-subtitle" id="loading-subtitle">Calling Groq AI — this may take a few seconds</div>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div class="quiz-error-state" id="quiz-error">
                        <span class="error-icon">⚠️</span>
                        <div class="error-content">
                            <div class="error-title">Generation Failed</div>
                            <div class="error-message" id="quiz-error-msg">Something went wrong. Please try again.</div>
                        </div>
                    </div>
                </div>

                <!-- QUIZ RESULTS PANEL (hidden until generated) -->
                <div id="quiz-results-wrapper" style="display:none">

                    <!-- Result Action Bar -->
                    <div class="quiz-result-action-bar">
                        <div>
                            <div class="section-label" id="quiz-result-label">Quiz Results</div>
                            <div class="section-sub" id="quiz-result-sub">Generated successfully</div>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <button class="success-btn" onclick="saveQuizToSupabase()" style="display:flex;align-items:center;gap:6px;padding:10px 18px;width:auto">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                    <polyline points="17 21 17 13 7 13 7 21"/>
                                    <polyline points="7 3 7 8 15 8"/>
                                </svg>
                                Save to Supabase
                            </button>
                            <button class="primary-btn" onclick="generateQuiz()" style="display:flex;align-items:center;gap:6px;padding:10px 18px;width:auto">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px">
                                    <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.86"/>
                                </svg>
                                Regenerate
                            </button>
                        </div>
                    </div>

                    <!-- Tab Switcher -->
                    <div class="quiz-tabs">
                        <button class="quiz-tab active" id="tab-pretest" onclick="switchQuizTab('pretest')">
                            📋 Pre-Test
                            <span class="quiz-tab-count">15 items</span>
                        </button>
                        <button class="quiz-tab" id="tab-activity" onclick="switchQuizTab('activity')">
                            ⚡ Activity
                            <span class="quiz-tab-count">5 items</span>
                        </button>
                        <button class="quiz-tab" id="tab-posttest" onclick="switchQuizTab('posttest')">
                            ✅ Post-Test
                            <span class="quiz-tab-count">15 items</span>
                        </button>
                    </div>

                    <!-- Pre-Test Panel -->
                    <div class="quiz-panel" id="panel-pretest">
                        <div class="quiz-panel-header pre-header">
                            <div class="quiz-panel-icon">📋</div>
                            <div>
                                <div class="quiz-panel-title">Pre-Test Questions</div>
                                <div class="quiz-panel-sub">15 multiple-choice questions — administered before the lesson</div>
                            </div>
                        </div>
                        <div class="quiz-questions-list" id="pretest-questions"></div>
                    </div>

                    <!-- Activity Panel -->
                    <div class="quiz-panel" id="panel-activity" style="display:none">
                        <div class="quiz-panel-header activity-header">
                            <div class="quiz-panel-icon">⚡</div>
                            <div>
                                <div class="quiz-panel-title">Classroom Activity</div>
                                <div class="quiz-panel-sub">5 hardcoded activities — same for all students</div>
                            </div>
                        </div>
                        <div class="quiz-questions-list" id="activity-questions"></div>
                    </div>

                    <!-- Post-Test Panel -->
                    <div class="quiz-panel" id="panel-posttest" style="display:none">
                        <div class="quiz-panel-header post-header">
                            <div class="quiz-panel-icon">✅</div>
                            <div>
                                <div class="quiz-panel-title">Post-Test Questions</div>
                                <div class="quiz-panel-sub">15 multiple-choice questions — administered after the lesson</div>
                            </div>
                        </div>
                        <div class="quiz-questions-list" id="posttest-questions"></div>
                    </div>

                </div>

                <!-- SAVED QUIZZES LIST -->
                <div class="modules-container" style="margin-top:24px">
                    <div class="section-label">Saved Quizzes</div>
                    <div class="section-sub">All quizzes stored in Database</div>
                    <div id="saved-quizzes-list">
                        <div class="empty-state">
                            <div class="empty-icon">🗂️</div>
                            <h4>No saved quizzes yet</h4>
                            <p>Generate and save a quiz to see it here.</p>
                        </div>
                    </div>
                </div>

            </div>
            <!-- END QUIZ PAGE -->

            <!-- PROFILE PAGE -->
            <div class="page" id="page-profile">
                <div class="hero-section">
                    <h1 class="welcome-title">My Profile</h1>
                    <p class="welcome-subtitle">Manage your account and preferences</p>
                </div>

                <div class="modules-container">
                    <div class="section-label">Account Information</div>
                    <div class="section-sub">Update your personal details</div>
                    <div class="field-row">
                        <label for="p-name">Full Name</label>
                        <input type="text" id="p-name" placeholder="Your full name" maxlength="80" autocomplete="off">
                    </div>
                    <div class="field-row">
                        <label for="p-email">Email Address</label>
                        <input type="email" id="p-email" placeholder="your@email.com" maxlength="120" autocomplete="off">
                    </div>
                    <div class="field-row">
                        <label for="p-subject">Subject / Department</label>
                        <input type="text" id="p-subject" placeholder="e.g. Mathematics" maxlength="80" autocomplete="off">
                    </div>
                    <div class="save-row">
                        <button class="btn-cancel">Cancel</button>
                        <button class="btn-save" onclick="saveProfile()">Save Changes</button>
                    </div>
                </div>

                <div class="modules-container">
                    <div class="section-label">Recent Activity</div>
                    <div class="section-sub">Your latest actions on the platform</div>
                    <div id="profile-activity">
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <h4>No recent activity</h4>
                            <p>Your actions will appear here.</p>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
    <button class="nav-item active" data-page="home">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Home</span>
        <div class="nav-dot"></div>
    </button>
    <button class="nav-item" data-page="students">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>Students</span>
        <div class="nav-dot"></div>
    </button>
    <button class="nav-item" data-page="progress">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        <span>Progress</span>
        <div class="nav-dot"></div>
    </button>
    <button class="nav-item" data-page="quiz">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>Quiz</span>
        <div class="nav-dot"></div>
    </button>
    <button class="nav-item" data-page="modules">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        <span>Modules</span>
        <div class="nav-dot"></div>
    </button>
    <button class="nav-item" data-page="reports">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <span>Reports</span>
        <div class="nav-dot"></div>
    </button>
    <button class="nav-item" data-page="profile">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>Profile</span>
        <div class="nav-dot"></div>
    </button>
</nav>

<!-- SEND FEEDBACK MODAL -->
<div class="modal-overlay" id="modal-feedback">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Send Feedback</span>
            <button class="modal-close" onclick="closeModal('modal-feedback')">✕</button>
        </div>
        <p style="font-size:13px;color:var(--text-3);margin-bottom:12px">
            To: <strong id="fb-student-name" style="color:var(--text)"></strong>
        </p>

        <div id="fb-history-wrap" style="margin-bottom:16px;display:none">
            <label style="font-size:12px;font-weight:600;color:var(--text-3);display:block;margin-bottom:6px">Feedback History</label>
            <div id="fb-history-list" style="max-height:180px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding-right:4px"></div>
        </div>

        <div class="field-row">
            <label for="fb-type">Feedback Type</label>
            <select id="fb-type">
                <option value="encouragement">Encouragement</option>
                <option value="improvement">Needs Improvement</option>
                <option value="praise">Praise</option>
                <option value="reminder">Reminder</option>
            </select>
        </div>
        <div class="field-row">
            <label for="fb-message">Message</label>
            <textarea id="fb-message" rows="4" placeholder="Write your feedback message here…" maxlength="500"></textarea>
        </div>
        <div class="save-row">
            <button class="btn-cancel" onclick="closeModal('modal-feedback')">Cancel</button>
            <button class="btn-save" onclick="saveFeedback()">Send Feedback</button>
        </div>
    </div>
</div>

<!-- ADD MODULE MODAL -->
<div class="modal-overlay" id="modal-add-module">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="mod-modal-title">Add Module</span>
            <button class="modal-close" onclick="cancelModule()">✕</button>
        </div>
        <div class="field-row">
            <label for="mod-title">Module Title</label>
            <input type="text" id="mod-title" placeholder="e.g. Introduction to Algebra" maxlength="100" autocomplete="off">
        </div>
        <div class="field-row">
            <label for="mod-topic">Topic</label>
            <select id="mod-topic">
                <option value="Module 1: Sequences and Series">Module 1: Sequences and Series</option>
                <option value="Module 2: Polynomials">Module 2: Polynomials</option>
                <option value="Module 3: Advanced Equations">Module 3: Advanced Equations</option>
            </select>
        </div>
        <div class="field-row">
            <label for="mod-desc">Description</label>
            <textarea id="mod-desc" rows="3" placeholder="Brief description of this module…" maxlength="300"></textarea>
        </div>
        <div class="field-row">
            <label for="mod-status">Status</label>
            <input type="text" id="mod-status" value="Draft" readonly>
        </div>
        <div class="field-row">
            <label for="mod-file">Upload File <span style="font-weight:500;text-transform:none;font-size:11px;color:var(--text-4)">(optional — PDF, DOC, DOCX, PPT, PPTX)</span></label>
            <div class="file-upload-area" id="mod-file-area">
                <input type="file" id="mod-file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.png,.jpg,.jpeg">
                <div class="file-upload-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </div>
                <div class="file-upload-label">Click or drag &amp; drop to upload</div>
                <div class="file-upload-hint">Supported: <span>PDF, DOC, DOCX, PPT, PPTX</span> — max 20 MB</div>
            </div>
            <div class="file-preview" id="mod-file-preview">
                <div class="file-preview-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div class="file-preview-info">
                    <div class="file-preview-name" id="mod-file-name">—</div>
                    <div class="file-preview-size" id="mod-file-size">—</div>
                </div>
                <button type="button" class="file-preview-remove" id="mod-file-remove" title="Remove file" onclick="clearFile()">✕</button>
            </div>
        </div>
        <input type="hidden" id="mod-edit-id" value="">
        <div class="save-row">
            <button class="btn-cancel" onclick="cancelModule()">Cancel</button>
            <button class="btn-save" onclick="saveModule()">Save Module</button>
        </div>
    </div>
</div>

<!-- VIEW SAVED QUIZ MODAL -->
<div class="modal-overlay" id="modal-view-quiz">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <span class="modal-title" id="view-quiz-title">Quiz Details</span>
            <button class="modal-close" onclick="closeModal('modal-view-quiz')">✕</button>
        </div>
        <div id="view-quiz-body" style="max-height:60vh;overflow-y:auto"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
</body>
</html>
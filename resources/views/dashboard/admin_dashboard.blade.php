<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Math Learning Assistant - Admin Dashboard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- SweetAlert2 CSS via CDN (reliable fallback). The JS is bundled through
         Vite (admin_dashboard.js imports it directly), so no CDN script here. -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    {{-- PDF/Excel export (Users, Analytics, Modules, Activity) is lazy-loaded by
         loadExportLibs() in admin_dashboard.js on first use, not loaded here. --}}

    {{-- Vite: compiles admin_dashboard.css + admin_dashboard.js --}}
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
            email: "{{ auth()->user()->email }}",
        };

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
        'resources/css/dashboard/admin_dashboard.css',
        'resources/js/polling.js',
        'resources/js/nav-progress.js',
        'resources/js/dashboard/admin_dashboard.js'
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Home
            </button>
            <button class="sidebar-item" data-page="users">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Users
            </button>
            <button class="sidebar-item" data-page="analytics">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Analytics
            </button>
            <button class="sidebar-item" data-page="content">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Content
            </button>
            <button class="sidebar-item" data-page="modules">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Modules
            </button>
            <button class="sidebar-item" data-page="activity">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Activity
            </button>
            <button class="sidebar-item" data-page="settings">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Settings
            </button>
        </nav>
        <div class="sidebar-logout">
            <button class="sidebar-logout-btn" onclick="confirmLogout()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </button>
        </div>
    </aside>

    {{-- Laravel logout form --}}
    <form id="logout-form" method="POST" action="{{ route('admin.logout') }}" style="display:none;">
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
            <button class="logout-btn" onclick="confirmLogout()">Logout</button>
        </header>

        <main class="main-content">

            <!-- HOME PAGE -->
            <div class="page active" id="page-home">
                <div class="hero-section">
                    <h1 class="welcome-title">System Administration</h1>
                    <p class="welcome-subtitle">Manage users, permissions, and platform settings</p>
                </div>

                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card" onclick="navigate('users')">
                            <div class="metric-header">
                                <span class="metric-label">Total Users</span>
                                <div class="icon-container blue-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="m-total">0</div>
                            <div class="metric-sub">registered accounts</div>
                        </div>
                        <div class="metric-card" onclick="navigate('users')">
                            <div class="metric-header">
                                <span class="metric-label">Active Students</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="m-students">0</div>
                            <div class="metric-sub">enrolled learners</div>
                        </div>
                        <div class="metric-card" onclick="navigate('users')">
                            <div class="metric-header">
                                <span class="metric-label">Teachers</span>
                                <div class="icon-container orange-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="m-teachers">0</div>
                            <div class="metric-sub">active educators</div>
                        </div>
                        <div class="metric-card" onclick="navigate('users')">
                            <div class="metric-header">
                                <span class="metric-label">Pending Approvals</span>
                                <div class="icon-container purple-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="m-pending">0</div>
                            <div class="metric-sub">awaiting review</div>
                        </div>
                    </div>
                </div>

                <section class="modules-container">
                    <div class="section-label">Recent System Activity</div>
                    <div class="section-sub">Latest user registrations and system events</div>
                    <div id="home-activity-log">
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <h4>No activity yet</h4>
                            <p>Events will appear here as users interact with the platform.</p>
                        </div>
                    </div>
                    <button class="view-topics-btn" onclick="navigate('activity')">View All Activity</button>
                </section>

                <div class="bottom-grid">
                    <div class="action-card">
                        <div class="action-icon-wrap blue-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <div class="action-content">
                            <h3>User Management</h3>
                            <p>Manage accounts, roles, and permissions</p>
                            <button class="primary-btn" onclick="navigate('users')">Manage Users</button>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon-wrap green-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div class="action-content">
                            <h3>Roles &amp; Permissions</h3>
                            <p>See what each role can access</p>
                            <button class="outline-btn" onclick="navigate('settings')">View Roles</button>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon-wrap orange-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        </div>
                        <div class="action-content">
                            <h3>Analytics</h3>
                            <p>View platform usage and performance metrics</p>
                            <button class="primary-btn" onclick="navigate('analytics')">View Analytics</button>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon-wrap purple-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        </div>
                        <div class="action-content">
                            <h3>Activity Tracking</h3>
                            <p>Monitor active users and engagement</p>
                            <button class="primary-btn" onclick="navigate('activity')">Track Activity</button>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon-wrap green-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <div class="action-content">
                            <h3>Content Management</h3>
                            <p>Upload and validate learning materials</p>
                            <button class="outline-btn" onclick="navigate('content')">Manage Content</button>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon-wrap orange-theme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </div>
                        <div class="action-content">
                            <h3>System Settings</h3>
                            <p>Configure platform preferences and features</p>
                            <button class="primary-btn" onclick="navigate('settings')">System Settings</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- USERS PAGE -->
            <div class="page" id="page-users">
                <div class="hero-section">
                    <h1 class="welcome-title">User Management</h1>
                    <p class="welcome-subtitle">Manage all registered users, roles, and account status</p>
                </div>
                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Total Users</span><div class="icon-container blue-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div></div><div class="metric-value" id="u-total">0</div><div class="metric-sub">registered accounts</div></div>
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Students</span><div class="icon-container green-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div></div><div class="metric-value" id="u-students">0</div><div class="metric-sub">enrolled learners</div></div>
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Teachers</span><div class="icon-container orange-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div></div><div class="metric-value" id="u-teachers">0</div><div class="metric-sub">active educators</div></div>
                    </div>
                </div>
                <div class="modules-container">
                    @if ($pendingTeachers->count() > 0)
                    <div class="section-label">⚠️ Pending Teacher Approvals</div>
                    <div class="section-sub">{{ $pendingTeachers->count() }} teacher(s) awaiting approval &middot; <a href="{{ route('admin.teacher-approvals') }}" style="color:#1e88e5;font-weight:600;">Manage all &rarr;</a></div>
                    <div class="pending-teachers-list" style="margin-bottom: 2rem;">
                        @foreach ($pendingTeachers as $teacher)
                        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; margin-bottom: 0.75rem; border-radius: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; color: #78350f;">{{ $teacher->name }}</div>
                                <div style="font-size: 0.875rem; color: #92400e;">{{ $teacher->email }}</div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <form method="POST" action="{{ route('admin.teacher.approve', $teacher->id) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.teacher.reject', $teacher->id) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;">Reject</button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                        <div>
                            <div class="section-label">All Users</div>
                            <div class="section-sub">Search, filter, and manage user accounts</div>
                        </div>
                        <button class="primary-btn" onclick="showExportPicker('users')" style="display:flex;align-items:center;gap:6px;padding:10px 18px;width:auto">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Export
                        </button>
                    </div>
                    <div class="toolbar">
                        <input type="text" class="search-input" id="user-search" placeholder="🔍  Search by name or email…" oninput="debounceUserSearch()" maxlength="100" autocomplete="off">
                        <select class="filter-select" id="user-role-filter" onchange="filterUsers()">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Student ID</th><th>Role</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="users-tbody"></tbody>
                        </table>
                    </div>
                    <div class="pagination" id="user-pagination"></div>
                </div>
            </div>

            <!-- ANALYTICS PAGE -->
            <div class="page" id="page-analytics">
                <div class="hero-section">
                    <h1 class="welcome-title">Analytics</h1>
                    <p class="welcome-subtitle">Platform usage, engagement, and performance metrics</p>
                </div>
                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Daily Active</span><div class="icon-container green-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div></div><div class="metric-value" id="a-dau">0</div><div class="metric-sub">users today</div></div>
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Avg. Pre-Test</span><div class="icon-container blue-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg></div></div><div class="metric-value" id="a-score-pre">—</div><div class="metric-sub">platform average</div></div>
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Avg. Post-Test</span><div class="icon-container purple-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div></div><div class="metric-value" id="a-score">—</div><div class="metric-sub">platform average</div></div>
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Improvement</span><div class="icon-container green-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div></div><div class="metric-value" id="a-improvement">—</div><div class="metric-sub">post-test vs pre-test</div></div>
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Modules Done</span><div class="icon-container orange-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div></div><div class="metric-value" id="a-completions">0</div><div class="metric-sub">total completions</div></div>
                    </div>
                </div>
                <div class="chart-container">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                        <div>
                            <div class="chart-title">Weekly User Registrations</div>
                            <div class="chart-sub">New signups per day over the last 7 days</div>
                        </div>
                        <button class="primary-btn" onclick="showExportPicker('analytics')" style="display:flex;align-items:center;gap:6px;padding:10px 18px;width:auto">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Export
                        </button>
                    </div>
                    <div id="reg-chart">
                        <div class="empty-state"><div class="empty-icon">📊</div><h4>No registration data yet</h4><p>Charts will populate as users join.</p></div>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-title">Subject Completion Rates</div>
                    <div class="chart-sub">How far students have progressed in each subject</div>
                    <div id="subject-progress">
                        <div class="empty-state"><div class="empty-icon">📈</div><h4>No progress data yet</h4><p>Data appears as students complete modules.</p></div>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-title">User Distribution</div>
                    <div class="chart-sub">Breakdown of platform roles</div>
                    <div id="donut-row">
                        <div class="empty-state"><div class="empty-icon">🍩</div><h4>No users yet</h4><p>Add users to see role distribution.</p></div>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-title">Top Performing Students</div>
                    <div class="chart-sub">Students with the highest overall scores</div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Rank</th><th>Student</th><th>Score</th><th>Modules</th><th>Streak</th></tr></thead>
                            <tbody id="top-students-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- CONTENT PAGE -->
            <div class="page" id="page-content">
                <div class="hero-section">
                    <h1 class="welcome-title">Content Management</h1>
                    <p class="welcome-subtitle">Upload, manage, and validate learning materials</p>
                </div>

                <!-- Stats Row -->
                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Pending</span>
                                <div class="icon-container orange-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="c-pending">0</div>
                            <div class="metric-sub">awaiting review</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Approved</span>
                                <div class="icon-container green-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="c-approved">0</div>
                            <div class="metric-sub">published materials</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-label">Rejected</span>
                                <div class="icon-container red-theme">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </div>
                            </div>
                            <div class="metric-value" id="c-rejected">0</div>
                            <div class="metric-sub">returned for revision</div>
                        </div>
                    </div>
                </div>

                <!-- Validation Queue -->
                <div class="modules-container">
                    <div class="toolbar" style="margin-bottom:14px;align-items:flex-start">
                        <div style="flex:1">
                            <div class="section-label">🔍 Admin Validation Queue</div>
                            <div class="section-sub" style="margin-bottom:0">Review, approve, or reject submitted materials</div>
                        </div>
                        <select class="filter-select" id="content-status-filter" onchange="filterContent()">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div id="content-queue-body"></div>
                </div>
            </div>

            <!-- MODULES PAGE -->
            <div class="page" id="page-modules">
                <div class="hero-section">
                    <h1 class="welcome-title">Modules</h1>
                    <p class="welcome-subtitle">Upload and manage learning modules platform-wide</p>
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
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                        <div>
                            <div class="section-label">All Modules</div>
                            <div class="section-sub">Browse, add, and manage learning modules — same library teachers upload to</div>
                        </div>
                        <button class="primary-btn" onclick="showExportPicker('modules')" style="display:flex;align-items:center;gap:6px;padding:10px 18px;width:auto">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Export
                        </button>
                    </div>
                    <div class="toolbar">
                        <input type="text" class="search-input" id="module-search"
                               placeholder="🔍  Search modules…" oninput="filterModules()"
                               maxlength="100" autocomplete="off">
                        <select class="filter-select" id="module-topic-filter" onchange="filterModules()">
                            <option value="">All Topics</option>
                            <option value="Module 1: Sequences and Series">Module 1: Sequences and Series</option>
                            <option value="Module 2: Polynomials">Module 2: Polynomials</option>
                            <option value="Module 3: Advanced Equations">Module 3: Advanced Equations</option>
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

            <!-- ACTIVITY PAGE -->
            <div class="page" id="page-activity">
                <div class="hero-section">
                    <h1 class="welcome-title">Activity Tracking</h1>
                    <p class="welcome-subtitle">Full log of user actions and system events</p>
                </div>
                <div class="metrics-scroll-wrap">
                    <div class="metrics-grid">
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Events Today</span><div class="icon-container blue-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div></div><div class="metric-value" id="ac-events">0</div><div class="metric-sub">since midnight</div></div>
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Logins Today</span><div class="icon-container green-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg></div></div><div class="metric-value" id="ac-logins">0</div><div class="metric-sub">sessions started</div></div>
                        <div class="metric-card"><div class="metric-header"><span class="metric-label">Errors</span><div class="icon-container red-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div></div><div class="metric-value" id="ac-errors">0</div><div class="metric-sub">system warnings</div></div>
                    </div>
                </div>
                <div class="modules-container">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                        <div>
                            <div class="section-label">Event Timeline</div>
                            <div class="section-sub">Search, filter, and manage the platform's activity log</div>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <button class="primary-btn" onclick="openArchivedLogs()" style="display:flex;align-items:center;gap:6px;padding:10px 18px;width:auto;background:#6b7280">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
                                    <path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/>
                                </svg>
                                Archived Logs
                            </button>
                            <button class="primary-btn" onclick="openClearOldLogs()" style="display:flex;align-items:center;gap:6px;padding:10px 18px;width:auto;background:#f97316">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
                                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>
                                </svg>
                                Clear Old Logs
                            </button>
                            <button class="primary-btn" onclick="showExportPicker('activity')" style="display:flex;align-items:center;gap:6px;padding:10px 18px;width:auto">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Export
                            </button>
                        </div>
                    </div>
                    <div class="toolbar">
                        <input type="text" class="search-input" id="activity-search" placeholder="🔍  Search by name, email, or activity…" oninput="debounceActivitySearch()" maxlength="255" autocomplete="off">
                        <select class="filter-select" id="activity-type-filter" onchange="filterActivityLog()">
                            <option value="">All Types</option>
                            <option value="registration">Account Created</option>
                            <option value="login">Login</option>
                            <option value="content">Content</option>
                            <option value="system">System / Admin</option>
                            <option value="error">Errors</option>
                        </select>
                        <select class="filter-select" id="activity-role-filter" onchange="filterActivityLog()">
                            <option value="">All Users</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                        <input type="date" class="filter-select" id="activity-date" onchange="filterActivityLog()" title="Filter by date">
                    </div>
                    <div class="activity-timeline" id="activity-timeline">
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <h4>No events logged yet</h4>
                            <p>Activity will appear here as users interact with the platform.</p>
                        </div>
                    </div>
                    <div class="pagination" id="activity-pagination"></div>
                </div>
            </div>

            <!-- SETTINGS PAGE -->
            <div class="page" id="page-settings">
                <div class="hero-section">
                    <h1 class="welcome-title">System Settings</h1>
                    <p class="welcome-subtitle">Configure platform preferences, roles, and features</p>
                </div>
                <div class="settings-section">
                    <h3>Platform Info</h3>
                    <div class="desc">Basic information about your Math Learning platform</div>
                    <div class="field-row"><label>Platform Name</label><input type="text" id="s-platform-name" placeholder="Math Learning Assistant" maxlength="80" autocomplete="off"></div>
                    <div class="field-row"><label>Admin Email</label><input type="email" id="s-admin-email" value="{{ auth()->user()->email }}" readonly></div>
                    <div class="field-row"><label>Platform Description</label><textarea id="s-desc" rows="3" placeholder="Describe your platform…" maxlength="500"></textarea></div>
                    <div class="save-row">
                        <button class="btn-cancel">Cancel</button>
                        <button class="btn-save" onclick="savePlatformInfo()">Save Changes</button>
                    </div>
                </div>
                <div class="settings-section">
                    <h3>Notifications</h3>
                    <div class="desc">Choose what notifications the system sends</div>
                    <div class="toggle-row">
                        <div class="toggle-info"><div class="toggle-title">New User Registrations</div><div class="toggle-sub">Notify admin when a new user joins</div></div>
                        <label class="toggle"><input type="checkbox" id="notif-registration"><span class="toggle-slider"></span></label>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-info"><div class="toggle-title">Content Published</div><div class="toggle-sub">Alert when teacher publishes a module</div></div>
                        <label class="toggle"><input type="checkbox" id="notif-content"><span class="toggle-slider"></span></label>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-info"><div class="toggle-title">System Errors</div><div class="toggle-sub">Immediate alert on critical errors</div></div>
                        <label class="toggle"><input type="checkbox" id="notif-errors"><span class="toggle-slider"></span></label>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-info"><div class="toggle-title">Weekly Report</div><div class="toggle-sub">Receive a weekly usage digest</div></div>
                        <label class="toggle"><input type="checkbox" id="notif-weekly"><span class="toggle-slider"></span></label>
                    </div>
                    <div class="save-row">
                        <button class="btn-cancel">Cancel</button>
                        <button class="btn-save" onclick="saveSettings('Notification')">Save Preferences</button>
                    </div>
                </div>
                <div class="settings-section">
                    <h3>Roles &amp; Permissions</h3>
                    <div class="desc">What each role can currently access — fixed by role, not editable here</div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Permission</th><th>Admin</th><th>Teacher</th><th>Student</th></tr></thead>
                            <tbody>
                                <tr><td>View Dashboard</td><td>✅</td><td>✅</td><td>✅</td></tr>
                                <tr><td>Manage Users</td><td>✅</td><td>❌</td><td>❌</td></tr>
                                <tr><td>Create Content</td><td>✅</td><td>✅</td><td>❌</td></tr>
                                <tr><td>View Analytics</td><td>✅</td><td>✅</td><td>❌</td></tr>
                                <tr><td>System Settings</td><td>✅</td><td>❌</td><td>❌</td></tr>
                                <tr><td>Take Quizzes</td><td>✅</td><td>✅</td><td>✅</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="settings-section">
                    <h3>Feature Flags</h3>
                    <div class="desc">Enable or disable platform features</div>
                    <div class="toggle-row">
                        <div class="toggle-info"><div class="toggle-title">Maintenance Mode</div><div class="toggle-sub">Temporarily disable access for non-admins</div></div>
                        <label class="toggle"><input type="checkbox" id="feat-maintenance"><span class="toggle-slider"></span></label>
                    </div>
                </div>
                <div class="settings-section" style="border-color:#fca5a5">
                    <h3 style="color:var(--red)">Danger Zone</h3>
                    <div class="desc">Irreversible actions — proceed with caution. To manage activity logs (delete individual entries, archive old ones, or export), see the <a href="javascript:void(0)" onclick="navigate('activity')" style="color:var(--blue);font-weight:600">Activity tab</a>.</div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        <button class="danger-btn" style="max-width:200px" onclick="confirmDanger('Reset Platform','This will reset all settings to factory defaults.')">Reset Platform</button>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
    <button class="nav-item active" data-page="home"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg><span>Home</span><div class="nav-dot"></div></button>
    <button class="nav-item" data-page="users"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>Users</span><div class="nav-dot"></div></button>
    <button class="nav-item" data-page="analytics"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg><span>Analytics</span><div class="nav-dot"></div></button>
    <button class="nav-item" data-page="content"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>Content</span><div class="nav-dot"></div></button>
    <button class="nav-item" data-page="modules"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg><span>Modules</span><div class="nav-dot"></div></button>
    <button class="nav-item" data-page="settings"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg><span>Settings</span><div class="nav-dot"></div></button>
</nav>

<!-- ADD USER MODAL -->
<div class="modal-overlay" id="modal-user">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="modal-user-title">Add New User</span>
            <button class="modal-close" onclick="closeModal('modal-user')">✕</button>
        </div>
        <div class="field-row"><label>Full Name</label><input type="text" id="u-name" placeholder="e.g. Juan dela Cruz" maxlength="80" autocomplete="off"></div>
        <div class="field-row"><label>Email</label><input type="email" id="u-email" placeholder="user@example.com" maxlength="120" autocomplete="off"></div>
        <div class="field-row"><label>Role</label>
            <select id="u-role"><option value="student">Student</option><option value="teacher">Teacher</option><option value="admin">Admin</option></select>
        </div>
        <div class="field-row"><label>Status</label>
            <select id="u-status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
        </div>
        <div class="save-row">
            <button class="btn-cancel" onclick="closeModal('modal-user')">Cancel</button>
            <button class="btn-save" onclick="saveUser()">Save User</button>
        </div>
    </div>
</div>

<!-- ARCHIVED ACTIVITY LOGS MODAL -->
<div class="modal-overlay" id="modal-archived-logs">
    <div class="modal" style="max-width:640px;max-height:85vh;overflow-y:auto">
        <div class="modal-header">
            <span class="modal-title">Archived Activity Logs</span>
            <button class="modal-close" onclick="closeModal('modal-archived-logs')">✕</button>
        </div>
        <p style="font-size:12.5px;color:var(--text-3);margin:-8px 0 14px">Logs archived via "Clear Old Logs" — restore them to the active timeline or delete them permanently.</p>
        <div class="activity-timeline" id="archived-timeline">
            <div class="empty-state">
                <div class="empty-icon">🗄️</div>
                <h4>No archived logs</h4>
                <p>Logs you archive will appear here.</p>
            </div>
        </div>
        <div class="pagination" id="archived-pagination"></div>
    </div>
</div>

<!-- ADD/EDIT MODULE MODAL -->
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

</body>
</html>
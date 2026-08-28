/* ================================
   resources/js/dashboard/student_dashboard.js
   ================================ */

import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

document.addEventListener('DOMContentLoaded', function () {

    /* ================================
       NAVIGATION
       ================================ */
    function navigate(page, moduleNum) {
        // Modules is a separate Blade view — use Laravel route
        if (page === 'modules') {
            // The route URL is injected by the Blade template via a meta tag
            const modulesUrl = document.querySelector('meta[name="modules-url"]');
            const base = modulesUrl ? modulesUrl.getAttribute('content') : '/student/modules';
            // Which module card/button was actually clicked — the modules
            // page scrolls straight to that section instead of always
            // landing back at Module 1.
            window.startNavProgress?.();
            window.location.href = moduleNum ? `${base}#module${moduleNum}` : base;
            return;
        }

        // Smooth page transition
        const allPages = document.querySelectorAll('.page');
        allPages.forEach(p => {
            if (p.classList.contains('active')) {
                p.classList.remove('active');
                p.style.display = 'none';
            }
        });

        setTimeout(() => {
            const target = document.getElementById('page-' + page);
            if (target) {
                target.classList.add('active');
                target.style.display = 'block';
            }

            document.querySelectorAll('.nav-item[data-page]').forEach(b => {
                b.classList.toggle('active', b.dataset.page === page);
            });
            document.querySelectorAll('.sidebar-item[data-page]').forEach(b => {
                b.classList.toggle('active', b.dataset.page === page);
            });

            // ✅ Trigger loadDownloads when downloads page is opened
            if (page === 'downloads') {
                loadDownloads();
            }

            // ✅ Load feedback when the feedback page is opened
            if (page === 'feedback') {
                loadFeedback();
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 150);
    }

    document.querySelectorAll('[data-page]').forEach(btn => {
        btn.addEventListener('click', function () {
            navigate(this.dataset.page);
        });
    });

    // Expose globally for Blade inline onclick attributes
    window.navigate = navigate;

    // ✅ Auto-load if downloads page is already active on load
    if (document.getElementById('page-downloads')?.classList.contains('active')) {
        loadDownloads();
    }

    // ============================================
    // SETUP REVEAL ANIMATIONS
    // ============================================
    // Removed: animations disabled

    // ============================================
    // SEARCH FUNCTIONALITY FOR DOWNLOADS
    // ============================================
    const downloadSearch = document.querySelector('.download-search input');
    if (downloadSearch) {
        downloadSearch.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.download-item');
            let visibleCount = 0;

            items.forEach(item => {
                const name = item.querySelector('.download-name')?.textContent || '';
                const matches = !query || name.toLowerCase().includes(query);
                item.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            }
        );

            // Show "no results" message
            const noResults = document.querySelector('.no-results');
            if (visibleCount === 0 && !noResults) {
                const container = document.querySelector('.downloads-list');
                if (container) {
                    const msg = document.createElement('div');
                    msg.className = 'no-results';
                    msg.textContent = 'No downloads found matching your search';
                    container.appendChild(msg);
                }
            } else if (visibleCount > 0 && noResults) {
                noResults.remove();
            }
        });
    }

    /* ============================================================
       DOWNLOADS — merge published modules + hardcoded fallbacks
       ============================================================ */

    // Hardcoded fallbacks keyed by topic → array of items
    const HARDCODED_DOWNLOADS = {
        'Module 1: Sequences and Series': [
            { name: 'Arithmetic Sequence',          file: 'Arithmetic Sequence.pdf',          size: '472 KB' },
            { name: 'Geometric Sequence',           file: 'Geometric Sequence.pdf',           size: '532 KB' },
            { name: 'Harmonic Sequence',            file: 'Harmonic Sequence.pdf',            size: '89 KB'  },
            { name: 'Fibonacci Sequence',           file: 'Fibonacci Sequence.pdf',           size: '70 KB'  },
            { name: 'Finite and Infinite Sequence', file: 'Finite and Infinite Sequence.pdf', size: '512 KB' },
        ],
        'Module 2: Polynomials': [
            { name: 'Division of Polynomials',                    file: 'Division of Polynomials.pdf',         size: '514 KB' },
            { name: 'The Remainder Theorem and Factor Theorem',   file: 'The Remainder and Factor Theorem.pdf', size: '577 KB' },
            { name: 'Polynomial Equations',                       file: 'Polynomial Equation.pdf',             size: '661 KB' },
        ],
        'Module 3: Advanced Equations': [
            { name: 'Rational Equations',    file: 'Rational Functions.pdf',      size: '1.1 MB' },
            { name: 'Radical Equations',     file: 'Radical Equations.pdf',       size: '3.9 MB' },
            { name: 'Exponential Functions', file: 'Exponential Functions.pdf',   size: '1.5 MB' },
            { name: 'Logarithmic Functions', file: 'Logarithmic Functions.pdf',   size: '1.3 MB' },
        ],
    };

    const TOPIC_THEME = {
        'Module 1: Sequences and Series': 'green-theme',
        'Module 2: Polynomials':          'orange-theme',
        'Module 3: Advanced Equations':   'purple-theme',
    };

    const TOPIC_LABEL = {
        'Module 1: Sequences and Series': 'Module 1 — Sequences and Series',
        'Module 2: Polynomials':          'Module 2 — Polynomials and Polynomial Equations',
        'Module 3: Advanced Equations':   'Module 3 — Advanced Equations and Functions',
    };

    // Utility: HTML escape for XSS prevention
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, char => map[char]);
    }

    // Utility: student_progress.created_at is a Postgres `timestamp without
    // time zone` column, so PostgREST returns it with no zone suffix (e.g.
    // "2026-08-20T10:26:06.554052"). JS's Date parser treats a zone-less
    // date-time string as LOCAL time, not UTC — which is what the value
    // actually is (written via toISOString() elsewhere in this file) — so
    // without this, every relative/absolute time shown here would be wrong
    // by the viewer's UTC offset. Append Z so it's always parsed as UTC.
    function parseUtcDate(dateLike) {
        if (!dateLike) return null;
        const str = String(dateLike);
        const hasZone = /[Zz]|[+-]\d{2}:?\d{2}$/.test(str);
        const date = new Date(hasZone ? str : `${str}Z`);
        return isNaN(date.getTime()) ? null : date;
    }

    // Utility: relative "time ago" label for a timestamp; falls back to a
    // plain date once it's more than a week old, and to '—' for missing data.
    function timeAgo(dateLike) {
        const date = parseUtcDate(dateLike);
        if (!date) return '—';
        const diffMs = Date.now() - date.getTime();
        const minutes = Math.floor(diffMs / 60000);
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        const days = Math.floor(hours / 24);
        if (days < 7) return `${days}d ago`;
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    async function loadDownloads() {
        let publishedModules = [];
        try {
            const res = await fetch('/student/modules/list', {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            if (res.ok) publishedModules = (await res.json()).modules || [];
        } catch (e) {
            console.warn('Could not load published modules:', e.message);
            return;
        }

        function normalizeTopic(topic) {
            if (!topic) return '';
            const t = topic.toLowerCase();
            if (t.includes('module 1') || t.includes('sequence')) return 'mod1';
            if (t.includes('module 2') || t.includes('polynomial')) return 'mod2';
            if (t.includes('module 3') || t.includes('advanced') || t.includes('rational') || t.includes('radical') || t.includes('exponential') || t.includes('logarithm')) return 'mod3';
            return null;
        }

        const THEME = { mod1: 'green-theme', mod2: 'orange-theme', mod3: 'purple-theme' };

        publishedModules.forEach(m => {
            const sectionKey = normalizeTopic(m.topic);
            if (!sectionKey) return;

            const section = document.getElementById('section-' + sectionKey);
            if (!section) return;

            const theme = THEME[sectionKey];
            const item  = {
                name: m.title || 'Teacher Upload',
                size: 'Uploaded by teacher',
                url: `/student/modules/${m.id}/download`,
                isPublished: true,
            };

            section.insertAdjacentHTML('beforeend', renderDownloadItem(item, theme));
        });
    }

    function renderDownloadItem(item, theme) {
        const publishedBadge = item.isPublished
            ? `<span class="status-badge badge-good" style="font-size:10px;margin-left:6px">Teacher</span>`
            : '';

        const dlButton = item.url
            ? `<button class="dl-btn" onclick="handleDownload('${escapeHtml(item.url)}', true)">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
               </button>`
            : `<button class="dl-btn" onclick="handleDownload('${escapeHtml(item.file)}', false)">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
               </button>`;

        return `
        <div class="download-item">
            <div class="download-icon ${theme}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="download-info">
                <span class="download-name">${escapeHtml(item.name)}${publishedBadge}</span>
                <span class="download-meta">${escapeHtml(item.size)}</span>
            </div>
            ${dlButton}
        </div>`;
    }

    // Expose globally for Blade inline onclick attributes
    window.loadDownloads = loadDownloads;

    /* ================================
       TOAST (SweetAlert2 helper)
       ================================ */
    window.toast = function (icon, title) {
        if (typeof Swal === 'undefined') return;
        Swal.fire({
            position: 'center',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            icon: icon,
            title: title,
        });
    };

    /* ================================
       CHANGE PASSWORD
       ================================ */
    window.clearPasswordForm = function () {
        ['pw-current', 'pw-new', 'pw-confirm'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    };

    window.updatePassword = async function () {
        const current = document.getElementById('pw-current')?.value || '';
        const next = document.getElementById('pw-new')?.value || '';
        const confirm = document.getElementById('pw-confirm')?.value || '';

        const res = await fetch('/student/account/password', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({
                current_password: current,
                password: next,
                password_confirmation: confirm,
            }),
        });
        if (res.status === 419) {
            window.toast('error', 'Your session has expired. Please refresh the page and try again.');
            return;
        }

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            window.toast('error', firstError || data.message || 'Could not update password.');
            return;
        }

        window.toast('success', 'Password updated successfully!');
        window.clearPasswordForm();
    };

    /* ================================
       CONFIRM LOGOUT
       ================================ */
    window.confirmLogout = function () {
        if (typeof Swal === 'undefined') {
            document.getElementById('logout-form').submit();
            return;
        }
        Swal.fire({
            position: 'center',
            title: 'Are you sure?',
            text: 'You will be logged out of your account.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout!',
            cancelButtonText: 'Cancel',
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Logged out',
                    text: 'Goodbye!',
                    timer: 1500,
                    timerProgressBar: true,
                    showConfirmButton: false,
                }).then(() => {
                    document.getElementById('logout-form').submit();
                });
            }
        });
    };

    /* ================================
       SUMMATIVE TEST — Quiz Logic
       ================================ */
    const quizQuestions = [
        { q: "In an arithmetic sequence, the first term is 3 and the common difference is 4. What is the 6th term?",     choices: ["19","23","21","17"],                                                                                          answer: 1 },
        { q: "What is the sum of the first 5 terms of the geometric sequence 2, 6, 18, 54, ...?",                        choices: ["162","242","182","122"],                                                                                      answer: 1 },
        { q: "Which of the following is a polynomial expression?",                                                        choices: ["x⁻² + 3","√x + 2","3x³ − 2x + 1","1/x + 5"],                                                              answer: 2 },
        { q: "What is the remainder when P(x) = x³ − 2x² + x − 5 is divided by (x − 2)?",                              choices: ["-3","-1","3","1"],                                                                                            answer: 0 },
        { q: "Factor completely: x² − 9",                                                                                choices: ["(x−3)²","(x+3)(x−3)","(x−9)(x+1)","(x+3)²"],                                                              answer: 1 },
        { q: "Which is the correct form of the quadratic formula?",                                                       choices: ["x = (b ± √(b²−4ac)) / 2a","x = (−b ± √(b²+4ac)) / 2a","x = (−b ± √(b²−4ac)) / 2a","x = (−b ± √(b²−4ac)) / a"], answer: 2 },
        { q: "Solve for x: 2^x = 32",                                                                                    choices: ["4","5","6","3"],                                                                                              answer: 1 },
        { q: "What is log₂(64)?",                                                                                        choices: ["5","8","6","7"],                                                                                              answer: 2 },
        { q: "If f(x) = 2x + 3, what is f(4)?",                                                                         choices: ["10","11","12","9"],                                                                                            answer: 1 },
        { q: "An infinite geometric series converges when the common ratio r satisfies:",                                 choices: ["r > 1","|r| < 1","r = 1","r < 0"],                                                                            answer: 1 },
    ];

    let quizCurrent = 0;
    let quizAnswers = new Array(quizQuestions.length).fill(null);
    let quizScore   = 0;

    function startQuiz() {
        quizCurrent = 0;
        quizAnswers = new Array(quizQuestions.length).fill(null);
        quizScore   = 0;
        document.getElementById('quiz-start-screen').style.display    = 'none';
        document.getElementById('quiz-question-screen').style.display = 'block';
        document.getElementById('quiz-result-screen').style.display   = 'none';
        renderQuestion();
    }

    function renderQuestion() {
        const q     = quizQuestions[quizCurrent];
        const total = quizQuestions.length;

        document.getElementById('quiz-q-label').textContent      = `Question ${quizCurrent + 1} of ${total}`;
        document.getElementById('quiz-progress-bar').style.width = `${((quizCurrent + 1) / total) * 100}%`;
        document.getElementById('quiz-question-text').textContent = q.q;
        document.getElementById('quiz-score-badge').textContent   = `Score: ${quizScore}`;

        const choicesEl = document.getElementById('quiz-choices');
        choicesEl.innerHTML = '';
        ['A','B','C','D'].forEach((letter, i) => {
            const btn = document.createElement('button');
            btn.className = 'quiz-choice';
            if (quizAnswers[quizCurrent] === i) btn.classList.add('selected');
            btn.innerHTML = `<span class="choice-letter">${letter}</span>${q.choices[i]}`;
            btn.addEventListener('click', () => selectAnswer(i));
            choicesEl.appendChild(btn);
        });

        const prevBtn = document.getElementById('quiz-prev-btn');
        prevBtn.style.opacity = quizCurrent === 0 ? '0.3' : '1';
        prevBtn.disabled      = quizCurrent === 0;

        document.getElementById('quiz-next-btn').textContent =
            quizCurrent === total - 1 ? 'Submit ✓' : 'Next →';
    }

    function selectAnswer(index) {
        quizAnswers[quizCurrent] = index;
        document.querySelectorAll('.quiz-choice').forEach((btn, i) => {
            btn.classList.toggle('selected', i === index);
            const letter = btn.querySelector('.choice-letter');
            letter.style.background = i === index ? 'var(--blue)' : '';
            letter.style.color      = i === index ? 'white'       : '';
        });
    }

    function quizNext() {
        if (quizAnswers[quizCurrent] === null) {
            window.toast('warning', 'Please select an answer first!');
            return;
        }
        if (quizCurrent < quizQuestions.length - 1) {
            quizCurrent++;
            renderQuestion();
        } else {
            submitQuiz();
        }
    }

    function quizPrev() {
        if (quizCurrent > 0) { quizCurrent--; renderQuestion(); }
    }

    function submitQuiz() {
        quizScore = quizAnswers.reduce((acc, ans, i) =>
            acc + (ans === quizQuestions[i].answer ? 1 : 0), 0);

        const total = quizQuestions.length;
        const pct   = Math.round((quizScore / total) * 100);

        document.getElementById('quiz-question-screen').style.display = 'none';
        document.getElementById('quiz-result-screen').style.display   = 'block';
        document.getElementById('quiz-result-score').textContent      = `${quizScore}/${total}`;

        let emoji = '😢', title = 'Keep Practicing!', msg = 'Review your modules and try again.';
        if      (pct >= 90) { emoji = '🏆'; title = 'Outstanding!'; msg = 'Excellent work! You mastered the material.'; }
        else if (pct >= 75) { emoji = '🎉'; title = 'Great Job!';   msg = 'You passed! Keep reviewing for mastery.';   }
        else if (pct >= 50) { emoji = '👍'; title = 'Good Effort!'; msg = 'Almost there — review your weak areas.';    }

        document.getElementById('quiz-result-emoji').textContent = emoji;
        document.getElementById('quiz-result-title').textContent = title;
        document.getElementById('quiz-result-msg').textContent   = `${pct}% — ${msg}`;
    }

    function retakeQuiz() {
        document.getElementById('quiz-result-screen').style.display = 'none';
        startQuiz();
    }

    /* ================================
       SUPABASE PROGRESS TRACKING
       ================================ */

    // Real topic set, matching module.blade.php's TOPIC_ORDER / MQ_TOPICS
    const MODULE_TOPICS = {
        mod1: ['ari', 'geo', 'har', 'fib', 'fin'],
        mod2: ['div', 'rem', 'poly'],
        mod3: ['rat', 'rad', 'exp', 'log'],
    };
    const ALL_TOPICS = [...MODULE_TOPICS.mod1, ...MODULE_TOPICS.mod2, ...MODULE_TOPICS.mod3];

    // Short topic_key -> full display name, matching module.blade.php's topic list.
    const TOPIC_NAMES = {
        ari:  'Arithmetic Sequence',
        geo:  'Geometric Sequence',
        har:  'Harmonic Sequence',
        fib:  'Fibonacci Sequence',
        fin:  'Finite and Infinite Sequence',
        div:  'Division of Polynomials',
        rem:  'Remainder & Factor Theorem',
        poly: 'Polynomial Equations',
        rat:  'Rational Equations',
        rad:  'Radical Equations',
        exp:  'Exponential Functions',
        log:  'Logarithmic Functions',
    };

    const Progress = {
        userId: window.__USER__?.id ?? null,
        _rowsCache: null,

        // Student progress is read/written through authenticated Laravel
        // endpoints — the server ties every row to the session user, so this
        // client never names a session_id.
        async loadRows(force = false) {
            if (!this.userId) {
                console.warn('Student progress tracking disabled: User not authenticated');
                return [];
            }
            if (this._rowsCache && !force) return this._rowsCache;
            try {
                const res = await fetch('/student/progress', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                });
                const data = res.ok ? await res.json() : { progress: [] };
                this._rowsCache = Array.isArray(data.progress) ? data.progress : [];
            } catch (err) {
                console.error('Error loading progress:', err.message);
                this._rowsCache = [];
            }
            return this._rowsCache;
        },

        // ✅ Save a summative-test attempt (dashboard's own review quiz)
        async saveSummativeAttempt(score, total) {
            if (!this.userId) {
                console.warn('Student ID not available for quiz save');
                return;
            }
            try {
                await fetch('/student/progress', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        topic_key: 'summative',
                        phase: 'post',
                        score,
                        total,
                        passed: score >= Math.ceil(total * 0.6),
                    }),
                });
                this._rowsCache = null; // invalidate cache
                console.log('Quiz score saved:', score, '/', total);
            } catch (err) {
                console.error('Error saving quiz score:', err.message);
            }
        },

        // ✅ Aggregate real stats from the loaded rows
        computeStats(rows) {
            const completedTopics = new Set(
                rows.filter(r => r.phase === 'post' && ALL_TOPICS.includes(r.topic_key)).map(r => r.topic_key)
            );

            const perModule = {};
            Object.entries(MODULE_TOPICS).forEach(([mod, topics]) => {
                const done = topics.filter(t => completedTopics.has(t)).length;
                perModule[mod] = { done, total: topics.length, pct: Math.round((done / topics.length) * 100) };
            });

            const overallPct = Math.round((completedTopics.size / ALL_TOPICS.length) * 100);

            const avgOf = phase => {
                const scored = rows.filter(r => r.phase === phase && ALL_TOPICS.includes(r.topic_key) && r.total > 0);
                if (!scored.length) return null;
                return Math.round(scored.reduce((sum, r) => sum + (r.score / r.total) * 100, 0) / scored.length);
            };
            const avgPre  = avgOf('pre');
            const avgPost = avgOf('post');
            const improvement = (avgPre === null || avgPost === null) ? null : avgPost - avgPre;

            // Streak: consecutive calendar days (ending today or yesterday) with at least one attempt
            const days = new Set(rows.map(r => parseUtcDate(r.created_at)?.toDateString()).filter(Boolean));
            let streak = 0;
            const cursor = new Date();
            if (!days.has(cursor.toDateString())) cursor.setDate(cursor.getDate() - 1);
            while (days.has(cursor.toDateString())) {
                streak++;
                cursor.setDate(cursor.getDate() - 1);
            }

            // Reading-progress rows (phase 'reading') track scroll %, not a graded
            // attempt — showing them here would mislabel as "Post-Test" with a
            // nonsensical score, so only real pre/post-test attempts are listed.
            const recent = rows
                .filter(r => r.phase === 'pre' || r.phase === 'post')
                .sort((a, b) => (parseUtcDate(b.created_at)?.getTime() ?? 0) - (parseUtcDate(a.created_at)?.getTime() ?? 0))
                .slice(0, 5);

            return { completedTopics, perModule, overallPct, attempts: rows.length, streak, recent, avgPre, avgPost, improvement };
        },

        async init() {
            const rows = await this.loadRows();
            console.log('Student progress loaded:', rows.length, 'attempts');
        }
    };

    // ✅ Hook quiz submission to save score
    const originalSubmitQuiz = window.submitQuiz;
    window.submitQuiz = function() {
        originalSubmitQuiz.call(this);
        // Save quiz score to Supabase
        Progress.saveSummativeAttempt(quizScore, quizQuestions.length);
    };

    /* ================================
       DASHBOARD ANALYTICS — real data, no hardcoded values
       ================================ */
    async function loadDashboardAnalytics() {
        const rows = await Progress.loadRows();
        const stats = Progress.computeStats(rows);

        const setText = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
        const setWidth = (id, pct) => { const el = document.getElementById(id); if (el) el.style.width = pct + '%'; };

        // Home page
        setText('home-overall-progress', stats.overallPct + '%');
        setText('home-topics-done', `${stats.completedTopics.size}/${ALL_TOPICS.length}`);
        setText('home-streak', stats.streak);
        ['mod1', 'mod2', 'mod3'].forEach(mod => {
            setText(`home-${mod}-pct`, stats.perModule[mod].pct + '%');
            setWidth(`home-${mod}-fill`, stats.perModule[mod].pct);
            setText(`home-${mod}-icon`, stats.perModule[mod].done === stats.perModule[mod].total ? '✓' : '—');
        });

        // Progress page
        setText('progress-overall', stats.overallPct + '%');
        setText('progress-topics-done', `${stats.completedTopics.size}/${ALL_TOPICS.length}`);
        setText('progress-attempts', stats.attempts);
        setText('progress-avg-pre', stats.avgPre === null ? '—' : stats.avgPre + '%');
        setText('progress-improvement', stats.improvement === null ? '—' : `${stats.improvement >= 0 ? '+' : ''}${stats.improvement}%`);
        ['mod1', 'mod2', 'mod3'].forEach(mod => {
            setText(`progress-${mod}-pct`, stats.perModule[mod].pct + '%');
            setWidth(`progress-${mod}-fill`, stats.perModule[mod].pct);
        });

        // Recent activity
        const emptyEl = document.getElementById('recent-activity-empty');
        const listEl  = document.getElementById('recent-activity-list');
        if (listEl && emptyEl) {
            if (stats.recent.length === 0) {
                emptyEl.style.display = '';
                listEl.style.display  = 'none';
            } else {
                emptyEl.style.display = 'none';
                listEl.style.display  = '';
                listEl.innerHTML = stats.recent.map(r => {
                    const relative = timeAgo(r.created_at);
                    const parsedDate = parseUtcDate(r.created_at);
                    const absolute = parsedDate ? parsedDate.toLocaleString() : '';
                    const label = r.topic_key === 'summative' ? 'Summative Test' : (TOPIC_NAMES[r.topic_key] || `Topic "${r.topic_key}"`);
                    const phase = r.phase === 'pre' ? 'Pre-Test' : 'Post-Test';
                    const passed = !!r.passed;
                    const badge  = passed ? 'badge-good' : 'badge-warn';
                    const theme  = passed ? 'green-theme' : 'orange-theme';
                    const icon   = passed
                        ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
                        : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
                    return `<div class="module-item">
                        <div class="activity-row">
                            <div class="icon-container ${theme}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${icon}</svg>
                            </div>
                            <div class="activity-info">
                                <div class="module-title-row" style="margin-bottom:2px">
                                    <span class="module-name">${escapeHtml(label)} — ${phase}</span>
                                    <span class="status-badge ${badge}">${r.score}/${r.total}</span>
                                </div>
                                <div class="section-sub" style="margin:0" title="${escapeHtml(absolute)}">${relative}</div>
                            </div>
                        </div>
                    </div>`;
                }).join('');
            }
        }
    }

    /* ================================
       FEEDBACK — messages sent by the teacher
       ================================ */
    const FEEDBACK_ICONS = { encouragement: '💪', improvement: '📈', praise: '🌟', reminder: '⏰' };

    // Last-known feedback list, reused when a poll comes back 304 (nothing
    // changed) so every call below has real data to work with regardless of
    // whether this particular tick actually hit the network.
    let lastFeedbackItems = [];

    async function loadFeedback({ silent = false } = {}) {
        try {
            const data = await pollJson('/student/feedback');
            if (data !== null) {
                lastFeedbackItems = Array.isArray(data.feedback) ? data.feedback : [];
            }
            // data === null means 304 Not Modified — lastFeedbackItems is already current.
        } catch (e) {
            console.warn('Could not load feedback:', e.message);
            return; // keep showing whatever was last loaded rather than clearing the UI
        }

        const items = lastFeedbackItems;
        const unreadCount = items.filter(f => !f.read).length;
        [document.getElementById('feedback-unread-badge'), document.getElementById('feedback-unread-badge-mobile')].forEach(badge => {
            if (!badge) return;
            if (unreadCount > 0) { badge.textContent = unreadCount; badge.style.display = ''; }
            else { badge.style.display = 'none'; }
        });

        if (silent) return; // don't touch the page content unless the tab is actually open

        const emptyEl = document.getElementById('feedback-empty');
        const listEl  = document.getElementById('feedback-list');
        if (!emptyEl || !listEl) return;

        if (items.length === 0) {
            emptyEl.style.display = '';
            listEl.style.display  = 'none';
        } else {
            emptyEl.style.display = 'none';
            listEl.style.display  = '';
            listEl.innerHTML = items.map(f => `
                <div class="module-item">
                    <div class="module-title-row">
                        <span class="module-name">${FEEDBACK_ICONS[f.type] || '💬'} ${f.teacherName}</span>
                        ${f.read ? '' : '<span class="status-badge badge-warn">New</span>'}
                    </div>
                    <p style="margin:6px 0 4px;font-size:14px;color:var(--text-2)">${escapeHtml(f.message)}</p>
                    <div class="section-sub" style="margin:0">${f.date}</div>
                </div>`).join('');
        }

        // Mark everything as read now that the student has actually seen the list.
        if (unreadCount > 0) {
            try {
                await fetch('/student/feedback/read-all', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    credentials: 'same-origin',
                });
                // Reflect the read state locally too, so the next poll tick
                // (which may come back 304, i.e. no fresh data at all) doesn't
                // think these are still unread and re-send this request.
                items.forEach(f => { f.read = true; });
                [document.getElementById('feedback-unread-badge'), document.getElementById('feedback-unread-badge-mobile')]
                    .forEach(b => { if (b) b.style.display = 'none'; });
            } catch (e) {
                console.warn('Could not mark feedback as read:', e.message);
            }
        }
    }

    // Initialize progress tracking + analytics on page load
    Progress.init().then(loadDashboardAnalytics);

    // Load feedback quietly on page load so the unread badge is accurate
    // even before the student opens the Feedback tab.
    loadFeedback({ silent: true });

    // Keep feedback fresh without a manual reload: every 30s, check for new
    // messages. When the Feedback tab isn't the one currently open, this is
    // a silent badge-only check (mirrors the initial load above); when it
    // IS open, a poll tick behaves exactly like opening the tab — render
    // any new messages and mark them read. Pauses automatically while the
    // browser tab is hidden (see resources/js/polling.js).
    startPolling(() => {
        const feedbackTabOpen = document.getElementById('page-feedback')?.classList.contains('active');
        return loadFeedback({ silent: !feedbackTabOpen });
    }, 30000);

    /* ================================
       SUMMATIVE TEST LOCKING LOGIC
       ================================ */

    // ✅ Unlocked once the student has completed (post-tested) every real topic
    async function isSummativeUnlocked() {
        const rows = await Progress.loadRows();
        const stats = Progress.computeStats(rows);
        return stats.completedTopics.size >= ALL_TOPICS.length;
    }

    async function getCompletionProgress() {
        const rows = await Progress.loadRows();
        const stats = Progress.computeStats(rows);
        return {
            completed: stats.completedTopics.size,
            total: ALL_TOPICS.length,
            percentage: stats.overallPct,
            remaining: ALL_TOPICS.filter(t => !stats.completedTopics.has(t)),
        };
    }

    async function updateSummativeLockStatus() {
        const unlocked = await isSummativeUnlocked();
        const lockNotice = document.getElementById('summative-lock-notice');
        const initialCta = document.getElementById('initial-cta');
        const startButton = document.getElementById('start-summative-btn');
        const quizStartScreen = document.getElementById('quiz-start-screen');

        if (!unlocked) {
            const progress = await getCompletionProgress();
            const lockDisplay = `
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 13px; color: #92400e;">Complete all learning module topics first to unlock the summative test (${progress.completed}/${progress.total} done).</div>
                </div>
            `;

            document.getElementById('lock-progress-display').innerHTML = lockDisplay;
            lockNotice.style.display = 'block';
            initialCta.style.display = 'none';
            startButton.style.display = 'none';
            if (quizStartScreen) quizStartScreen.style.display = 'none';
        } else {
            // Hide lock notice
            lockNotice.style.display = 'none';
            initialCta.style.display = 'block';
            startButton.style.display = 'block';
            if (quizStartScreen) quizStartScreen.style.display = 'block';
        }
    }

    // ✅ Function to show test instructions
    window.showTestInstructions = function() {
        const initialCta = document.getElementById('initial-cta');
        const quizStartScreen = document.getElementById('quiz-start-screen');
        
        if (initialCta) initialCta.style.display = 'none';
        if (quizStartScreen) quizStartScreen.style.display = 'block';
    };

    // ✅ Check summative status when navigating to it
    const _originalNavigate = window.navigate;
    window.navigate = async function(page, ...rest) {
        if (page === 'summative') {
            await updateSummativeLockStatus();
        }
        _originalNavigate(page, ...rest);
    };

    // ✅ Wrap startQuiz() to prevent direct access when locked
    const _originalStartQuiz = startQuiz;
    window.startQuiz = async function() {
        const unlocked = await isSummativeUnlocked();
        if (!unlocked) {
            window.toast('warning', '🔒 Complete all module topics first to unlock this test!');
            return;
        }
        _originalStartQuiz();
    };

    // Expose quiz functions globally for Blade inline onclick attributes
    window.quizNext   = quizNext;
    window.quizPrev   = quizPrev;
    window.retakeQuiz = retakeQuiz;

});
window.handleDownload = function(filePathOrUrl, isDirectUrl = false) {
    try {
        // isDirectUrl → an already-built /student/modules/... route.
        // Otherwise → a curriculum PDF filename served by /student/modules/file.
        // The route responds with Content-Disposition: attachment, so a
        // plain link click saves the file instead of opening a tab.
        const href = isDirectUrl
            ? filePathOrUrl
            : `/student/modules/file?name=${encodeURIComponent(filePathOrUrl)}`;

        const link = document.createElement('a');
        link.href = href;
        link.rel = 'noopener';
        document.body.appendChild(link);
        link.click();
        link.remove();

        if (typeof toast === 'function') toast('success', 'Download started!');
    } catch (err) {
        if (typeof toast === 'function') toast('error', 'Error: ' + err.message);
    }
};
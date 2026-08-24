import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',

                // Shared behavior for the auth pages (sign-in/sign-up/reset) —
                // e.g. the password-visibility toggle used by x-input.
                'resources/js/auth.js',

                // Shared interval-poller used by dashboards to auto-refresh
                // data without a manual page reload — see startPolling()/
                // pollJson() in this file for how it works.
                'resources/js/polling.js',

                // Shared live-poller for the Teacher/Student approval-queue pages.
                'resources/js/approval-queue.js',

                // Thin top-of-page progress bar shown on real cross-page
                // navigation (not the dashboards' in-page tab-switching).
                'resources/js/nav-progress.js',

                'resources/css/homepage.css',
                'resources/js/homepage.js',

                // Login pages are otherwise self-contained Blade templates
                // (styles/behavior inline), aside from resources/js/auth.js above.
                // 'resources/css/login/student_login.css',
                // 'resources/js/login/student_login.js',
                // 'resources/css/login/teacher_login.css',
                // 'resources/js/login/teacher_login.js',
                // 'resources/css/login/admin_login.css',
                // 'resources/js/login/admin_login.js',

                // Dashboard Assets
                'resources/css/dashboard/student_dashboard.css',
                'resources/js/dashboard/student_dashboard.js',
                'resources/css/dashboard/chatbot.css',
                'resources/js/dashboard/chatbot.js',

                // Calculator tab + drag behavior layered onto the chatbot
                // widget above. Loaded after chatbot.js/.css so it can rely
                // on window.openChat/closeChat and win the CSS cascade for
                // its mobile/reduced-motion overrides. math.js itself is
                // NOT listed here — calculator-engine.js dynamic-imports it
                // lazily on first Calculator-tab click, so it's Rollup's
                // own async chunk, not part of this entry's initial bundle.
                'resources/css/dashboard/math-panel.css',
                'resources/js/dashboard/math-panel.js',

                'resources/css/dashboard/teacher_dashboard.css',
                'resources/js/dashboard/teacher_dashboard.js',
                'resources/css/dashboard/admin_dashboard.css',
                'resources/js/dashboard/admin_dashboard.js',
                // 'resources/css/dashboard/module_quiz.css',
                // 'resources/js/dashboard/module_quiz.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});

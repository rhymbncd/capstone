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

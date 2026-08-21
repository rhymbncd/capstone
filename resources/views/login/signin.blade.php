<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | Bubog NHS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/auth.js'])
</head>
<body class="auth-gradient-bg flex min-h-screen flex-col items-center justify-center p-4 font-sans sm:p-6">

  <a href="{{ route('homepage') }}" class="mb-4 inline-flex w-full max-w-4xl items-center gap-1.5 text-[13px] font-semibold text-white/70 transition-colors duration-150 hover:text-white">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Homepage
  </a>

  <div class="flex w-full max-w-4xl flex-col overflow-hidden rounded-lg shadow-overlay ring-1 ring-white/10 sm:flex-row">

    <!-- Branding panel -->
    <div class="auth-brand-panel flex flex-col items-center justify-center gap-4 px-6 py-8 sm:flex-[0_0_36%] sm:py-12">
      <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-white/20 bg-[#F7FAFB] sm:h-32 sm:w-32">
        <img
            src="{{ asset('image/587572187-777024998723535-6772324307557000990-n-fotor-20260519155328.png') }}"
            alt="Bubog National High School seal"
            class="h-full w-full object-cover"
            width="354" height="354"
        >
      </div>
      <div class="text-center text-[15px] font-bold tracking-wide text-white/90">Bubog National High School</div>
      <div class="flex gap-1.5">
        <span class="h-1.5 w-1.5 rounded-full bg-white/35"></span>
        <span class="h-1.5 w-1.5 rounded-full bg-white/65"></span>
        <span class="h-1.5 w-1.5 rounded-full bg-white/35"></span>
      </div>
    </div>

    <!-- Form panel -->
    <div class="flex flex-1 items-center justify-center bg-white p-6 sm:p-10">
      <div class="w-full max-w-[420px]">
        <h1 class="text-[28px] font-bold tracking-tight text-neutral-900">Welcome back</h1>
        <p id="sub-text" class="mb-6 mt-1 text-[13px] text-neutral-500">Sign in to your {{ $portalType ?? 'student' }} account</p>

        @if (session('success'))
          <x-alert variant="success" class="mb-5">{{ session('success') }}</x-alert>
        @endif

        @if (session('notification_error'))
          <x-alert variant="error" class="mb-5">{{ session('notification_error') }}</x-alert>
        @endif

        <div class="mb-5 grid grid-cols-3 gap-2" role="tablist" aria-label="Account type">
          <button type="button" role="tab" id="tab-student" aria-selected="{{ ($portalType ?? 'student') === 'student' ? 'true' : 'false' }}" onclick="setRole('student', this)">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <span>Student</span>
          </button>
          <button type="button" role="tab" id="tab-teacher" aria-selected="{{ ($portalType ?? 'student') === 'teacher' ? 'true' : 'false' }}" onclick="setRole('teacher', this)">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h20"/><path d="M21 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="m7 21 5-5 5 5"/></svg>
            <span>Teacher</span>
          </button>
          <button type="button" role="tab" id="tab-admin" aria-selected="{{ ($portalType ?? 'student') === 'admin' ? 'true' : 'false' }}" onclick="setRole('admin', this)">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>Admin</span>
          </button>
        </div>

        <button type="button" class="flex h-10 w-full items-center justify-center gap-2.5 rounded-md border border-neutral-200 text-[14px] font-medium text-neutral-700 transition-colors duration-150 hover:bg-neutral-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary" onclick="redirectToGoogle()">
          <svg width="17" height="17" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#FFC107" d="M43.6 20H24v8h11.1C33.5 33.2 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.8 2.9l5.7-5.7C33.9 6.5 29.2 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 19.7-8 19.7-20 0-1.3-.1-2.7-.4-4z"/>
            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.5 15.1 18.9 12 24 12c3 0 5.7 1.1 7.8 2.9l5.7-5.7C33.9 6.5 29.2 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
            <path fill="#4CAF50" d="M24 44c5.2 0 9.8-1.8 13.4-4.7l-6.2-5.2C29.4 35.6 26.8 36 24 36c-5.3 0-9.5-2.8-11.1-7H6.3C9.7 39.7 16.3 44 24 44z"/>
            <path fill="#1976D2" d="M43.6 20H24v8h11.1c-.8 2.3-2.3 4.2-4.3 5.5l6.2 5.2C40.5 35.5 44 30.2 44 24c0-1.3-.1-2.7-.4-4z"/>
          </svg>
          Continue with Google
        </button>

        <div class="my-4 flex items-center gap-2 text-[12px] text-neutral-500">
          <span class="h-px flex-1 bg-neutral-200"></span> or sign in with email <span class="h-px flex-1 bg-neutral-200"></span>
        </div>

        <form id="loginForm" method="POST" class="space-y-4">
          @csrf
          <x-input
              label="Email address" name="email" type="email" id="email"
              autocomplete="email" required placeholder="Enter your email"
              value="{{ old('email') }}" :error="$errors->first('email')"
          />

          <x-input
              label="Password" name="password" type="password" id="pw"
              autocomplete="current-password" required placeholder="••••••••"
              :error="$errors->first('password')"
          />

          <div class="flex items-center justify-between">
            <label class="flex cursor-pointer items-center gap-2 text-[13px] text-neutral-700">
              <input type="checkbox" name="remember" class="h-4 w-4 rounded border-neutral-300 accent-primary">
              Remember me for 30 days
            </label>
            <a
                href="{{ ($portalType ?? 'student') === 'admin' ? '#' : route(($portalType ?? 'student').'.password.request') }}"
                id="forgot-link"
                class="auth-link text-[13px] {{ ($portalType ?? 'student') === 'admin' ? 'hidden' : '' }}"
            >Forgot password?</a>
          </div>

          <x-button type="submit" variant="action" class="w-full">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Sign In
          </x-button>
        </form>

        <p class="mt-5 text-center text-[13px] text-neutral-500">Don't have an account? <a href="{{ route('signin-signup') }}" class="auth-link">Sign up free</a></p>
      </div>
    </div>
  </div>

<script>
const roleLabels = { student: 'Sign in to your student account', teacher: 'Sign in to your teacher account', admin: 'Sign in to your admin account' };
const roleRoutes = { student: '{{ route("student.login.submit") }}', teacher: '{{ route("teacher.login.submit") }}', admin: '{{ route("admin.login.submit") }}' };
const googleRoutes = { student: '{{ route("auth.google.redirect", "student") }}', teacher: '{{ route("auth.google.redirect", "teacher") }}', admin: '{{ route("auth.google.redirect", "admin") }}' };
const forgotRoutes = { student: '{{ route("student.password.request") }}', teacher: '{{ route("teacher.password.request") }}', admin: null };
let currentRole = '{{ $portalType ?? "student" }}';

const TAB_BASE = 'flex flex-col items-center gap-1 rounded-md border py-2 px-1 text-[12px] font-semibold transition-colors duration-150 cursor-pointer';
const TAB_ACTIVE = 'auth-tab-active';
const TAB_INACTIVE = 'auth-tab-inactive';

function paintTab(el, isActive) {
  el.className = TAB_BASE + ' ' + (isActive ? TAB_ACTIVE : TAB_INACTIVE);
  el.setAttribute('aria-selected', isActive ? 'true' : 'false');
}

function setRole(role, el) {
  currentRole = role;
  document.querySelectorAll('[role="tab"]').forEach(t => paintTab(t, t === el));
  document.getElementById('sub-text').textContent = roleLabels[role];
  document.getElementById('loginForm').action = roleRoutes[role];

  const forgotLink = document.getElementById('forgot-link');
  forgotLink.classList.toggle('hidden', !forgotRoutes[role]);
  forgotLink.href = forgotRoutes[role] || '#';
}

function redirectToGoogle() {
  window.location.href = googleRoutes[currentRole];
}

// Set initial form action + tab styling to match the portal actually visited
// (was previously hardcoded to 'student', so visiting /teacher/login or
// /admin/login directly and signing in without touching a tab first would
// silently submit as a student login and always fail).
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('loginForm').action = roleRoutes[currentRole];
  document.querySelectorAll('[role="tab"]').forEach(t => paintTab(t, t.id === 'tab-' + currentRole));
});
</script>
</body>
</html>

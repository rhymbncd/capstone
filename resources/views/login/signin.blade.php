<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | Bubog NHS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; font-family: 'Inter', sans-serif; }

    body {
      background: linear-gradient(180deg, #1E88E5 0%, #80DEEA 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
    }

    .back-link {
      width: 100%;
      max-width: 1100px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 14px;
      font-weight: 600;
      color: rgba(255,255,255,0.92);
      text-decoration: none;
      margin-bottom: 16px;
      transition: color .2s, transform .2s;
    }
    .back-link:hover { color: #fff; transform: translateX(-3px); }

    /* ── CARD: always row, left + right side by side ── */
    .card {
      width: 100%;
      max-width: 1100px;
      display: flex;
      flex-direction: row;        /* NEVER changes — always side by side */
      border-radius: 22px;
      overflow: hidden;
      box-shadow: 0 24px 64px rgba(0,0,0,0.22);
      min-height: 640px;
    }

    /* ── LEFT BRANDING SIDE ── */
    .img-side {
      flex: 0 0 38%;
      background: linear-gradient(160deg, #1565C0 0%, #42A5F5 60%, #80DEEA 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 20px;
      padding: 48px 36px;
    }

    .logo-circle {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      border: 3px solid rgba(255,255,255,0.55);
      background: rgba(255,255,255,0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
      transition: transform .3s, box-shadow .2s, border-color .2s;
    }
    .logo-circle:hover { transform: scale(1.08); border-color: rgba(255,255,255,0.9); box-shadow: 0 0 0 5px rgba(255,255,255,0.18); }
    .logo-circle img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

    .school-name {
      color: rgba(255,255,255,0.92);
      font-size: 15px;
      font-weight: 700;
      text-align: center;
      letter-spacing: .5px;
    }

    .dots { display: flex; gap: 7px; }
    .dots span { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.35); }
    .dots span:nth-child(2) { background: rgba(255,255,255,0.65); }

    /* ── RIGHT FORM SIDE ── */
    .form-side {
      flex: 1;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px 52px;
      overflow-y: auto;
    }
    .form-content { width: 100%; max-width: 420px; }

    h2 { font-size: 28px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
    .sub { font-size: 13px; color: #888; margin-bottom: 24px; }

    .role-tabs { display: flex; gap: 10px; margin-bottom: 22px; }
    .role-tab {
      flex: 1; border: 1.5px solid #e5e7eb; background: #fff;
      border-radius: 11px; padding: 8px 4px;
      display: flex; flex-direction: column; align-items: center; gap: 3px;
      font-size: 12px; font-weight: 600; color: #888;
      cursor: pointer; transition: .2s;
    }
    .role-tab i { font-size: 20px; }
    .role-tab.active { border-color: #1E88E5; color: #1E88E5; background: #EBF5FF; }
    .role-tab:hover:not(.active) { border-color: #93c5fd; color: #1E88E5; }

    .google-btn {
      width: 100%; padding: 13px; border: 1.5px solid #e5e7eb; border-radius: 11px;
      background: #fff; display: flex; align-items: center; justify-content: center;
      gap: 10px; font-size: 14px; font-weight: 500; color: #333;
      cursor: pointer; transition: background .2s;
    }
    .google-btn:hover { background: #f8fafc; }

    .divider {
      display: flex; align-items: center; gap: 8px;
      font-size: 12px; color: #bbb; margin: 16px 0;
    }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }

    .ig { margin-bottom: 15px; }
    .ig label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 5px; }
    .ig .iw { position: relative; display: flex; align-items: center; }
    .ig .iw i.ico { position: absolute; left: 13px; font-size: 15px; color: #aaa; pointer-events: none; }
    .ig input {
      width: 100%; padding: 13px 13px 13px 40px;
      background: #F1F5F9; border: 1.5px solid transparent;
      border-radius: 11px; font-size: 14px; color: #333;
      outline: none; transition: .2s;
    }
    .ig input:focus { background: #fff; border-color: #1E88E5; box-shadow: 0 0 0 3px rgba(30,136,229,0.12); }
    .eye { position: absolute; right: 13px; color: #aaa; font-size: 16px; cursor: pointer; }

    .row-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .row-meta label { display: flex; align-items: center; gap: 7px; font-size: 13px; color: #555; cursor: pointer; }
    .row-meta input[type="checkbox"] { accent-color: #1E88E5; width: 15px; height: 15px; }
    .forgot { font-size: 13px; color: #1E88E5; font-weight: 600; text-decoration: none; }
    .forgot:hover { text-decoration: underline; }

    .btn-main {
      width: 100%; padding: 15px; background: #1E88E5; color: #fff;
      border: none; border-radius: 11px; font-size: 16px; font-weight: 700;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      cursor: pointer; transition: background .2s, transform .1s;
    }
    .btn-main:hover { background: #1565C0; }
    .btn-main:active { transform: scale(0.99); }

    .bottom-link { text-align: center; font-size: 13px; color: #888; margin-top: 16px; }
    .bottom-link a { color: #1E88E5; font-weight: 600; text-decoration: none; }
    .bottom-link a:hover { text-decoration: underline; }

    /* ══════════════════════════════════════
       RESPONSIVE — side by side at ALL sizes
       vw-based so everything scales with screen width
    ══════════════════════════════════════ */

    @media (max-width: 900px) {
      .card { min-height: 560px; }
      .img-side { padding: 36px 24px; gap: 16px; }
      .logo-circle { width: 110px; height: 110px; }
      .school-name { font-size: 13px; }
      .form-side { padding: 36px 32px; }
      h2 { font-size: 24px; }
      .sub { font-size: 12px; margin-bottom: 20px; }
      .role-tab { font-size: 11px; }
      .role-tab i { font-size: 17px; }
      .ig input { padding: 11px 11px 11px 36px; font-size: 13px; }
      .ig .iw i.ico { left: 11px; font-size: 13px; }
      .google-btn { padding: 11px; font-size: 13px; }
      .btn-main { padding: 13px; font-size: 15px; }
    }

    @media (max-width: 640px) {
      body { padding: 2vw 2vw; }
      .back-link { font-size: 2.8vw; margin-bottom: 2vw; }
      .card { min-height: unset; border-radius: 3.5vw; }

      .img-side { flex: 0 0 36%; padding: 4vw 2.5vw; gap: 2.5vw; }
      .logo-circle { width: 18vw; height: 18vw; border-width: 0.4vw; }
      .school-name { font-size: 2.4vw; }
      .dots span { width: 1.4vw; height: 1.4vw; }

      .form-side { padding: 4vw 3.5vw; }
      h2 { font-size: 4.5vw; margin-bottom: 0.5vw; }
      .sub { font-size: 2.8vw; margin-bottom: 3vw; }

      .role-tabs { gap: 1.5vw; margin-bottom: 3vw; }
      .role-tab { padding: 1.5vw 0.8vw; font-size: 2.4vw; border-radius: 2vw; border-width: 0.3vw; }
      .role-tab i { font-size: 3.5vw; }

      .google-btn { padding: 2vw; font-size: 2.6vw; gap: 1.5vw; border-radius: 2vw; border-width: 0.3vw; }
      .divider { font-size: 2.2vw; margin: 2vw 0; }
      .divider::before, .divider::after { height: 0.2vw; }

      .ig { margin-bottom: 2vw; }
      .ig label { font-size: 2.3vw; margin-bottom: 0.8vw; }
      .ig input { padding: 2vw 2vw 2vw 6vw; font-size: 2.6vw; border-radius: 2vw; border-width: 0.3vw; }
      .ig .iw i.ico { left: 1.8vw; font-size: 2.8vw; }
      .eye { right: 1.8vw; font-size: 3vw; }

      .row-meta { margin-bottom: 2.5vw; }
      .row-meta label { font-size: 2.3vw; gap: 1vw; }
      .row-meta input[type="checkbox"] { width: 2.5vw; height: 2.5vw; }
      .forgot { font-size: 2.3vw; }

      .btn-main { padding: 2.5vw; font-size: 3vw; border-radius: 2vw; gap: 1.5vw; }
      .bottom-link { font-size: 2.3vw; margin-top: 2vw; }
    }
  </style>
</head>
<body>

<a href="{{ route('homepage') }}" class="back-link">
  <i class="fa-solid fa-arrow-left"></i> Back to Homepage
</a>

<div class="card">
  <div class="img-side">
    <div class="logo-circle">
      <img src="{{ asset('image/587572187-777024998723535-6772324307557000990-n-fotor-20260519155328.png') }}" alt="Bubog NHS Logo">
    </div>
    <div class="school-name">Bubog National High School</div>
    <div class="dots"><span></span><span></span><span></span></div>
  </div>

  <div class="form-side">
    <div class="form-content">
      <h2>Welcome back</h2>
      <p class="sub" id="sub-text">Sign in to your {{ $portalType ?? 'student' }} account</p>

      @if (session('success'))
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; border: 1px solid #c3e6cb;">
          {{ session('success') }}
        </div>
      @endif

      @if (session('notification_error'))
        <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; border: 1px solid #f5c6cb;">
          {{ session('notification_error') }}
        </div>
      @endif

      <div class="role-tabs">
        <button class="role-tab {{ ($portalType ?? 'student') === 'student' ? 'active' : '' }}" onclick="setRole('student', this)">
          <i class="fa-solid fa-user-graduate"></i><span>Student</span>
        </button>
        <button class="role-tab {{ ($portalType ?? 'student') === 'teacher' ? 'active' : '' }}" onclick="setRole('teacher', this)">
          <i class="fa-solid fa-chalkboard-user"></i><span>Teacher</span>
        </button>
        <button class="role-tab {{ ($portalType ?? 'student') === 'admin' ? 'active' : '' }}" onclick="setRole('admin', this)">
          <i class="fa-solid fa-lock"></i><span>Admin</span>
        </button>
      </div>

      <button class="google-btn" type="button" onclick="redirectToGoogle()">
        <svg width="17" height="17" viewBox="0 0 48 48">
          <path fill="#FFC107" d="M43.6 20H24v8h11.1C33.5 33.2 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.8 2.9l5.7-5.7C33.9 6.5 29.2 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 19.7-8 19.7-20 0-1.3-.1-2.7-.4-4z"/>
          <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.5 15.1 18.9 12 24 12c3 0 5.7 1.1 7.8 2.9l5.7-5.7C33.9 6.5 29.2 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
          <path fill="#4CAF50" d="M24 44c5.2 0 9.8-1.8 13.4-4.7l-6.2-5.2C29.4 35.6 26.8 36 24 36c-5.3 0-9.5-2.8-11.1-7H6.3C9.7 39.7 16.3 44 24 44z"/>
          <path fill="#1976D2" d="M43.6 20H24v8h11.1c-.8 2.3-2.3 4.2-4.3 5.5l6.2 5.2C40.5 35.5 44 30.2 44 24c0-1.3-.1-2.7-.4-4z"/>
        </svg>
        Continue with Google
      </button>

      <div class="divider">or sign in with email</div>

      <form id="loginForm" method="POST">
        @csrf
        <div class="ig">
          <label>Email address</label>
          <div class="iw">
            <i class="fa-solid fa-envelope ico"></i>
            <input type="email" name="email" id="email" placeholder="Enter your email" autocomplete="email" required value="{{ old('email') }}">
          </div>
          @error('email')<p style="color: red; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
        </div>

        <div class="ig">
          <label>Password</label>
          <div class="iw">
            <i class="fa-solid fa-lock ico"></i>
            <input type="password" name="password" id="pw" placeholder="••••••••" autocomplete="current-password" required>
            <i class="fa-solid fa-eye eye" onclick="togglePw()"></i>
          </div>
          @error('password')<p style="color: red; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
        </div>

        <div class="row-meta">
          <label><input type="checkbox" name="remember"> Remember me for 30 days</label>
          <a href="#" class="forgot">Forgot password?</a>
        </div>

        <button type="submit" class="btn-main">
          <i class="fa-solid fa-right-to-bracket"></i> Sign In
        </button>
      </form>

      <p class="bottom-link">Don't have an account? <a href="{{ route('signin-signup') }}">Sign up free</a></p>
    </div>
  </div>
</div>

<script>
const roleLabels = { student: 'Sign in to your student account', teacher: 'Sign in to your teacher account', admin: 'Sign in to your admin account' };
const roleRoutes = { student: '{{ route("student.login.submit") }}', teacher: '{{ route("teacher.login.submit") }}', admin: '{{ route("admin.login.submit") }}' };
const googleRoutes = { student: '{{ route("auth.google.redirect", "student") }}', teacher: '{{ route("auth.google.redirect", "teacher") }}', admin: '{{ route("auth.google.redirect", "admin") }}' };
let currentRole = '{{ $portalType ?? "student" }}';

function setRole(role, el) {
  currentRole = role;
  document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('sub-text').textContent = roleLabels[role];
  document.getElementById('loginForm').action = roleRoutes[role];
}

function togglePw() {
  const p = document.getElementById('pw');
  const icon = document.querySelector('.eye');
  p.type = p.type === 'password' ? 'text' : 'password';
  icon.className = p.type === 'password' ? 'fa-solid fa-eye eye' : 'fa-solid fa-eye-slash eye';
}

function redirectToGoogle() {
  window.location.href = googleRoutes[currentRole];
}

// Set initial form action to match the portal actually visited
// (was previously hardcoded to 'student', so visiting /teacher/login or
// /admin/login directly and signing in without touching a tab first would
// silently submit as a student login and always fail).
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('loginForm');
  form.action = roleRoutes[currentRole];
});
</script>
</body>
</html>
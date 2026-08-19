<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | Bubog NHS</title>
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
      align-items: flex-start;
      justify-content: center;
      padding: 36px 52px;
      overflow-y: auto;
    }
    .form-content { width: 100%; max-width: 420px; padding: 8px 0; }

    h2 { font-size: 28px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
    .sub { font-size: 13px; color: #888; margin-bottom: 20px; }

    .role-tabs { display: flex; gap: 10px; margin-bottom: 16px; }
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
      font-size: 12px; color: #bbb; margin: 12px 0;
    }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }

    .ig { margin-bottom: 12px; }
    .ig label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 5px; }
    .ig .iw { position: relative; display: flex; align-items: center; }
    .ig .iw i.ico { position: absolute; left: 13px; font-size: 15px; color: #aaa; pointer-events: none; }
    .ig input {
      width: 100%; padding: 11px 13px 11px 40px;
      background: #F1F5F9; border: 1.5px solid transparent;
      border-radius: 11px; font-size: 14px; color: #333;
      outline: none; transition: .2s;
    }
    .ig input:focus { background: #fff; border-color: #1E88E5; box-shadow: 0 0 0 3px rgba(30,136,229,0.12); }
    .eye { position: absolute; right: 13px; color: #aaa; font-size: 16px; cursor: pointer; }

    .pw-strength { margin-top: 4px; }
    .pw-bar { height: 3px; background: #e5e7eb; border-radius: 2px; overflow: hidden; }
    .pw-fill { height: 100%; width: 0; border-radius: 2px; transition: width .35s, background .35s; }
    .pw-label { font-size: 11px; margin-top: 3px; color: #9ca3af; }

    .terms { font-size: 11px; color: #aaa; text-align: center; margin-bottom: 10px; line-height: 1.6; }
    .terms a { color: #1E88E5; text-decoration: none; }
    .terms a:hover { text-decoration: underline; }

    .ig select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 13px center;
      background-size: 16px;
      padding-right: 40px;
    }
    
    .ig select:focus {
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' fill='none' stroke='%231E88E5' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    }

    /* ── Section picker ── */
    .section-picker-wrap { position: relative; margin-bottom: 12px; }
    .section-picker-wrap label {
      display: block; font-size: 12px; font-weight: 600;
      color: #555; margin-bottom: 6px;
    }
    .section-search-box {
      position: relative; display: flex; align-items: center;
    }
    .section-search-box i.ico {
      position: absolute; left: 13px; font-size: 15px;
      color: #aaa; pointer-events: none; z-index: 1;
    }
    .section-search-input {
      width: 100%;
      padding: 11px 13px 11px 40px;
      background: #F1F5F9;
      border: 1.5px solid transparent;
      border-radius: 11px;
      font-size: 14px; color: #333;
      outline: none; cursor: pointer;
      transition: .2s;
    }
    .section-search-input:focus {
      background: #fff;
      border-color: #1E88E5;
      box-shadow: 0 0 0 3px rgba(30,136,229,0.12);
      cursor: text;
    }
    .section-dropdown {
      position: absolute; top: calc(100% + 6px); left: 0; right: 0;
      background: #fff;
      border: 1.5px solid #e5e7eb;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.10);
      z-index: 100; overflow: hidden;
      display: none;
      max-height: 220px; overflow-y: auto;
    }
    .section-dropdown.open { display: block; }
    .section-option {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px;
      font-size: 14px; color: #333;
      cursor: pointer; transition: background .15s;
      border-bottom: 1px solid #f3f4f6;
    }
    .section-option:last-child { border-bottom: none; }
    .section-option:hover, .section-option.focused { background: #EBF5FF; color: #1E88E5; }
    .section-option .badge {
      width: 28px; height: 28px; border-radius: 8px;
      background: #EBF5FF; color: #1E88E5;
      font-size: 11px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .section-option.no-result { color: #aaa; cursor: default; }
    .section-option.no-result:hover { background: none; }
    .section-chevron {
      position: absolute; right: 13px;
      font-size: 14px; color: #aaa; pointer-events: none;
      transition: transform .2s;
    }
    .section-picker-wrap.open .section-chevron { transform: rotate(180deg); }

    .btn-main {
      width: 100%; padding: 14px; background: #1E88E5; color: #fff;
      border: none; border-radius: 11px; font-size: 16px; font-weight: 700;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      cursor: pointer; transition: background .2s, transform .1s;
    }
    .btn-main:hover { background: #1565C0; }
    .btn-main:active { transform: scale(0.99); }

    .bottom-link { text-align: center; font-size: 13px; color: #888; margin-top: 14px; }
    .bottom-link a { color: #1E88E5; font-weight: 600; text-decoration: none; }
    .bottom-link a:hover { text-decoration: underline; }

    /* ══════════════════════════════════════
       RESPONSIVE — side by side at ALL sizes
       vw-based so everything scales with screen width
    ══════════════════════════════════════ */

    @media (max-width: 900px) {
      .card { min-height: 580px; }
      .img-side { padding: 36px 24px; gap: 16px; }
      .logo-circle { width: 110px; height: 110px; }
      .school-name { font-size: 13px; }
      .form-side { padding: 30px 32px; }
      h2 { font-size: 24px; }
      .sub { font-size: 12px; margin-bottom: 16px; }
      .role-tab { font-size: 11px; }
      .role-tab i { font-size: 17px; }
      .ig { margin-bottom: 10px; }
      .ig input { padding: 10px 10px 10px 36px; font-size: 13px; }
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

      .two-col { gap: 2vw; margin-bottom: 0; }
      .ig { margin-bottom: 2vw; }
      .ig label { font-size: 2.3vw; margin-bottom: 0.8vw; }
      .ig input { padding: 2vw 2vw 2vw 6vw; font-size: 2.6vw; border-radius: 2vw; border-width: 0.3vw; }
      .ig .iw i.ico { left: 1.8vw; font-size: 2.8vw; }
      .eye { right: 1.8vw; font-size: 3vw; }
      .pw-label { font-size: 2.2vw; }

      .terms { font-size: 2.2vw; margin-bottom: 2vw; }
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
      <h2>Create account</h2>
      <p class="sub" id="sub-text">Sign up as {{ ($portalType ?? 'student') === 'admin' ? 'an administrator' : 'a '.($portalType ?? 'student') }}{{ ($portalType ?? 'student') === 'student' ? ' for free' : '' }}</p>

      @if ($errors->any())
        <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; border: 1px solid #f5c6cb;">
          @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
          @endforeach
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

      <div class="divider">or sign up with email</div>

      <form id="signupForm" method="POST" onsubmit="return validateSection(event)">
        @csrf
        <div class="two-col">
          <div class="ig">
            <label>First name</label>
            <div class="iw">
              <i class="fa-regular fa-user ico"></i>
              <input type="text" name="firstName" id="fname" placeholder="Juan" autocomplete="given-name" required value="{{ old('firstName') }}">
            </div>
            @error('firstName')<p style="color: red; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
          </div>
          <div class="ig">
            <label>Last name</label>
            <div class="iw">
              <i class="fa-regular fa-user ico"></i>
              <input type="text" name="lastName" id="lname" placeholder="dela Cruz" autocomplete="family-name" required value="{{ old('lastName') }}">
            </div>
            @error('lastName')<p style="color: red; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
          </div>
        </div>

        <div class="ig">
          <label>Email address</label>
          <div class="iw">
            <i class="fa-solid fa-envelope ico"></i>
            <input type="email" name="email" id="email" placeholder="Enter your email" autocomplete="email" required value="{{ old('email') }}">
          </div>
          @error('email')<p style="color: red; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
        </div>

        <div class="ig" id="student-id-row">
          <label>Student ID</label>
          <div class="iw">
            <i class="fa-solid fa-id-card ico"></i>
            <input type="text" name="student_id" id="student_id" placeholder="e.g. 24-1234" autocomplete="off" value="{{ old('student_id') }}">
          </div>
          @error('student_id')<p style="color: red; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
        </div>

        <div class="section-picker-wrap" id="section-row">
          <label for="section-search-input">Section</label>

          {{-- Hidden real select that gets submitted with the form --}}
          <select name="section_id" id="section_id" style="display:none">
            <option value="" disabled selected></option>
          </select>

          <div class="section-search-box">
            <i class="fa-solid fa-layer-group ico"></i>
            <input
              type="text"
              id="section-search-input"
              class="section-search-input"
              placeholder="Search or select a section…"
              autocomplete="off"
              readonly
            >
            <i class="fa-solid fa-chevron-down section-chevron"></i>
          </div>

          <div class="section-dropdown" id="section-dropdown">
            <div class="section-option no-result">
              <i class="fa-solid fa-circle-info" style="color:#ccc"></i>
              Loading sections...
            </div>
          </div>

          @error('section_id')
            <p style="color: #ef4444; font-size: 12px; margin-top: 5px;">{{ $message }}</p>
          @enderror
        </div>

        <div class="ig">
          <label>Password</label>
          <div class="iw">
            <i class="fa-solid fa-lock ico"></i>
            <input type="password" name="password" id="pw" placeholder="Create a strong password" oninput="checkStrength()" required>
            <i class="fa-solid fa-eye eye" onclick="togglePw('pw', this)"></i>
          </div>
          <div class="pw-strength">
            <div class="pw-bar"><div class="pw-fill" id="pw-fill"></div></div>
            <div class="pw-label" id="pw-label">At least 8 characters</div>
          </div>
          @error('password')<p style="color: red; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
        </div>

        <div class="ig">
          <label>Confirm password</label>
          <div class="iw">
            <i class="fa-solid fa-lock ico"></i>
            <input type="password" name="password_confirmation" id="pw2" placeholder="Repeat your password" required>
            <i class="fa-solid fa-eye eye" onclick="togglePw('pw2', this)"></i>
          </div>
        </div>

        <p class="terms">By creating an account you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></p>

        <button type="submit" class="btn-main">
          <i class="fa-solid fa-user-plus"></i> Create Account
        </button>
      </form>

      <p class="bottom-link">Already have an account? <a href="{{ route('signin-signin') }}">Sign in</a></p>
    </div>
  </div>
</div>

<script>
const roleLabels = { student: 'Sign up as a student for free', teacher: 'Sign up as a teacher', admin: 'Sign up as an administrator' };
const roleRoutes = { student: '{{ route("student.register") }}', teacher: '{{ route("teacher.register") }}', admin: '{{ route("admin.register") }}' };
const googleRoutes = { student: '{{ route("auth.google.redirect", "student") }}', teacher: '{{ route("auth.google.redirect", "teacher") }}', admin: '{{ route("auth.google.redirect", "admin") }}' };
let currentRole = '{{ $portalType ?? "student" }}';

function setRole(role, el) {
  currentRole = role;
  document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('sub-text').textContent = roleLabels[role];
  document.getElementById('signupForm').action = roleRoutes[role];
  document.getElementById('section-row').style.display = role === 'student' ? 'block' : 'none';
  document.getElementById('student-id-row').style.display = role === 'student' ? 'block' : 'none';
}

function redirectToGoogle() {
  window.location.href = googleRoutes[currentRole];
}

function togglePw(id, icon) {
  const p = document.getElementById(id);
  p.type = p.type === 'password' ? 'text' : 'password';
  icon.className = p.type === 'password' ? 'fa-solid fa-eye eye' : 'fa-solid fa-eye-slash eye';
}

function checkStrength() {
  const pw = document.getElementById('pw').value;
  const fill = document.getElementById('pw-fill');
  const label = document.getElementById('pw-label');
  let score = 0;
  if (pw.length >= 8) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const map = [
    { w: '0%', bg: '#e5e7eb', txt: 'At least 8 characters' },
    { w: '25%', bg: '#ef4444', txt: 'Weak' },
    { w: '50%', bg: '#f59e0b', txt: 'Fair' },
    { w: '75%', bg: '#3b82f6', txt: 'Good' },
    { w: '100%', bg: '#22c55e', txt: 'Strong' },
  ];
  fill.style.width = map[score].w;
  fill.style.background = map[score].bg;
  label.textContent = map[score].txt;
  label.style.color = map[score].bg;
}

// Validate section selection before form submission
function validateSection(event) {
  if (currentRole !== 'student') {
    return true; // Only validate section/student ID for students
  }

  const studentIdInput = document.getElementById('student_id');
  if (!studentIdInput.value.trim()) {
    event.preventDefault();
    studentIdInput.style.borderColor = '#ef4444';
    studentIdInput.parentElement.style.background = '';
    studentIdInput.style.background = '#fef2f2';
    studentIdInput.focus();

    let idErrorMsg = document.getElementById('student-id-error-msg');
    if (!idErrorMsg) {
      idErrorMsg = document.createElement('p');
      idErrorMsg.id = 'student-id-error-msg';
      idErrorMsg.style.cssText = 'color: #ef4444; font-size: 12px; margin-top: 5px;';
      document.getElementById('student-id-row').appendChild(idErrorMsg);
    }
    idErrorMsg.textContent = 'Please enter your student ID';

    return false;
  }
  studentIdInput.style.borderColor = '';
  studentIdInput.style.background = '';
  const idErrorMsg = document.getElementById('student-id-error-msg');
  if (idErrorMsg) idErrorMsg.textContent = '';

  const sectionId = document.getElementById('section_id').value.trim();
  const sectionInput = document.getElementById('section-search-input');

  if (!sectionId) {
    event.preventDefault();
    
    // Highlight the section field in red
    sectionInput.style.borderColor = '#ef4444';
    sectionInput.style.background = '#fef2f2';
    sectionInput.focus();
    
    // Show error message
    let errorMsg = document.getElementById('section-error-msg');
    if (!errorMsg) {
      errorMsg = document.createElement('p');
      errorMsg.id = 'section-error-msg';
      errorMsg.style.cssText = 'color: #ef4444; font-size: 12px; margin-top: 5px;';
      document.getElementById('section-row').appendChild(errorMsg);
    }
    errorMsg.textContent = 'Please select a section to continue';
    
    return false;
  }
  
  // Clear error styling if section is valid
  sectionInput.style.borderColor = '';
  sectionInput.style.background = '';
  const errorMsg = document.getElementById('section-error-msg');
  if (errorMsg) errorMsg.textContent = '';
  
  return true;
}

// Fetch sections from API on page load
function loadSections() {
  fetch('/api/sections')
    .then(response => response.json())
    .then(data => {
      const dropdown = document.getElementById('section-dropdown');
      const sections = data.sections || [];

      if (sections.length === 0) {
        dropdown.innerHTML = '<div class="section-option no-result"><i class="fa-solid fa-circle-info" style="color:#ccc"></i> No sections available yet</div>';
        return;
      }

      // Clear dropdown and populate with fetched sections
      dropdown.innerHTML = '';
      sections.forEach((section, index) => {
        const opt = document.createElement('div');
        opt.className = 'section-option';
        opt.dataset.value = section.id;
        opt.dataset.label = section.name;
        opt.innerHTML = `<span class="badge">${index + 1}</span> ${section.name}`;
        dropdown.appendChild(opt);

        // Add click handler
        opt.addEventListener('click', function() {
          const hidden = document.getElementById('section_id');
          const input = document.getElementById('section-search-input');
          hidden.value = this.dataset.value;
          input.value = this.dataset.label;
          
          // Clear error styling
          input.style.borderColor = '';
          input.style.background = '';
          const errorMsg = document.getElementById('section-error-msg');
          if (errorMsg) errorMsg.textContent = '';
          
          closeDrop();
        });
      });
    })
    .catch(error => {
      console.error('Error loading sections:', error);
      const dropdown = document.getElementById('section-dropdown');
      dropdown.innerHTML = '<div class="section-option no-result"><i class="fa-solid fa-circle-info" style="color:#ccc"></i> Error loading sections</div>';
    });
}

// ═════════════════════════════════════════════════════════════
// SECTION PICKER DROPDOWN HANDLER
// ═════════════════════════════════════════════════════════════

// Section Picker Variables (global so they can be accessed from dynamically added handlers)
let sectionPickerState = {
  wrap: null,
  input: null,
  dropdown: null,
  hidden: null,
  openDrop() {
    this.wrap.classList.add('open');
    this.dropdown.classList.add('open');
    this.input.removeAttribute('readonly');
    this.input.focus();
  },
  closeDrop() {
    this.wrap.classList.remove('open');
    this.dropdown.classList.remove('open');
    this.input.setAttribute('readonly', true);
  }
};

// Fetch sections from API on page load
function loadSections() {
  fetch('/api/sections')
    .then(response => response.json())
    .then(data => {
      const dropdown = document.getElementById('section-dropdown');
      const hiddenSelect = document.getElementById('section_id');
      const sections = data.sections || [];

      if (sections.length === 0) {
        dropdown.innerHTML = '<div class="section-option no-result"><i class="fa-solid fa-circle-info" style="color:#ccc"></i> No sections available yet</div>';
        return;
      }

      // Clear dropdown and populate with fetched sections
      dropdown.innerHTML = '';
      
      // Also populate the hidden select element
      hiddenSelect.innerHTML = '<option value="" disabled selected></option>';
      
      sections.forEach((section, index) => {
        // Add option to hidden select for form submission
        const selectOption = document.createElement('option');
        selectOption.value = section.id;
        selectOption.textContent = section.name;
        hiddenSelect.appendChild(selectOption);
        
        // Create visual dropdown option
        const opt = document.createElement('div');
        opt.className = 'section-option';
        opt.dataset.value = section.id;
        opt.dataset.label = section.name;
        opt.innerHTML = `<span class="badge">${index + 1}</span> ${section.name}`;
        dropdown.appendChild(opt);

        // Add click handler
        opt.addEventListener('click', function() {
          sectionPickerState.hidden.value = this.dataset.value;
          sectionPickerState.input.value = this.dataset.label;
          
          // Clear error styling
          sectionPickerState.input.style.borderColor = '';
          sectionPickerState.input.style.background = '';
          const errorMsg = document.getElementById('section-error-msg');
          if (errorMsg) errorMsg.textContent = '';
          
          sectionPickerState.closeDrop();
        });
      });
    })
    .catch(error => {
      console.error('Error loading sections:', error);
      const dropdown = document.getElementById('section-dropdown');
      dropdown.innerHTML = '<div class="section-option no-result"><i class="fa-solid fa-circle-info" style="color:#ccc"></i> Error loading sections</div>';
    });
}

// Initialize section picker dropdown handler
function initSectionPicker() {
  sectionPickerState.wrap = document.getElementById('section-row');
  sectionPickerState.input = document.getElementById('section-search-input');
  sectionPickerState.dropdown = document.getElementById('section-dropdown');
  sectionPickerState.hidden = document.getElementById('section_id');

  if (!sectionPickerState.wrap) return;

  sectionPickerState.input.addEventListener('click', () => sectionPickerState.openDrop());
  sectionPickerState.input.addEventListener('keydown', e => { if (!sectionPickerState.dropdown.classList.contains('open')) sectionPickerState.openDrop(); });

  sectionPickerState.input.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    let any = false;
    sectionPickerState.dropdown.querySelectorAll('.section-option').forEach(opt => {
      if (opt.classList.contains('no-result')) return;
      const match = opt.dataset.label.toLowerCase().includes(q);
      opt.style.display = match ? '' : 'none';
      if (match) any = true;
    });
    const noRes = sectionPickerState.dropdown.querySelector('.no-result');
    if (noRes) noRes.style.display = any ? 'none' : '';
  });

  document.addEventListener('click', e => {
    if (!sectionPickerState.wrap.contains(e.target)) sectionPickerState.closeDrop();
  });
}

// Set initial form action to match the portal actually visited (was
// previously hardcoded to 'student', so visiting /teacher/register or
// /admin/register directly and submitting without touching a tab first
// would silently submit as a student registration).
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('signupForm');
  form.action = roleRoutes[currentRole];
  document.getElementById('section-row').style.display = currentRole === 'student' ? 'block' : 'none';
  document.getElementById('student-id-row').style.display = currentRole === 'student' ? 'block' : 'none';
  initSectionPicker();
  loadSections();
});
</script>
</body>
</html>
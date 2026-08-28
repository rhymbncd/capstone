<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | Bubog NHS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/auth.js', 'resources/js/nav-progress.js'])
</head>
<body class="auth-gradient-bg flex min-h-screen flex-col items-center justify-center p-4 font-sans sm:p-6">

  <a href="{{ route('homepage') }}" class="mb-4 inline-flex w-full max-w-4xl items-center gap-1.5 text-[13px] font-semibold text-white/70 transition-colors duration-150 hover:text-white">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Homepage
  </a>

  <div class="flex w-full max-w-4xl flex-col overflow-hidden rounded-lg shadow-overlay ring-1 ring-white/10 sm:h-[640px] sm:flex-row">

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
    <div id="signup-scroll-panel" class="flex flex-1 items-start justify-center overflow-y-auto bg-white p-6 sm:p-10">
      <div class="w-full max-w-[420px] py-1">
        <h1 class="text-[28px] font-bold tracking-tight text-neutral-900">Create account</h1>
        <p id="sub-text" class="mb-5 mt-1 text-[13px] text-neutral-500">Sign up as a {{ $portalType ?? 'student' }}{{ ($portalType ?? 'student') === 'student' ? ' for free' : '' }}</p>

        @if ($errors->any())
          <x-alert variant="error" class="mb-5">
            @foreach ($errors->all() as $error)
              <p>{{ $error }}</p>
            @endforeach
          </x-alert>
        @endif

        @if (session('notification_error'))
          <x-alert variant="error" class="mb-5">{{ session('notification_error') }}</x-alert>
        @endif

        <div class="mb-4 grid grid-cols-2 gap-2" role="tablist" aria-label="Account type">
          <button type="button" role="tab" id="tab-student" aria-selected="{{ ($portalType ?? 'student') === 'student' ? 'true' : 'false' }}" onclick="setRole('student', this)">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <span>Student</span>
          </button>
          <button type="button" role="tab" id="tab-teacher" aria-selected="{{ ($portalType ?? 'student') === 'teacher' ? 'true' : 'false' }}" onclick="setRole('teacher', this)">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h20"/><path d="M21 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="m7 21 5-5 5 5"/></svg>
            <span>Teacher</span>
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
          <span class="h-px flex-1 bg-neutral-200"></span> or sign up with email <span class="h-px flex-1 bg-neutral-200"></span>
        </div>

        <form id="signupForm" method="POST" onsubmit="return validateSection(event)" class="space-y-4">
          @csrf
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-input label="First name" name="firstName" id="fname" autocomplete="given-name" required placeholder="Juan" value="{{ old('firstName') }}" :error="$errors->first('firstName')" />
            <x-input label="Last name" name="lastName" id="lname" autocomplete="family-name" required placeholder="dela Cruz" value="{{ old('lastName') }}" :error="$errors->first('lastName')" />
          </div>

          <x-input label="Email address" name="email" type="email" id="email" autocomplete="email" required placeholder="Enter your email" value="{{ old('email') }}" :error="$errors->first('email')" />

          <div id="student-id-row">
            <x-input label="Student ID" name="student_id" id="student_id" autocomplete="off" placeholder="e.g. 24-1234" value="{{ old('student_id') }}" :error="$errors->first('student_id')" />
          </div>

          <div class="relative" id="section-row">
            <label for="section-search-input" class="mb-1.5 block text-[13px] font-medium text-neutral-700">Section</label>

            {{-- Hidden real select that gets submitted with the form --}}
            <select name="section_id" id="section_id" class="hidden">
              <option value="" disabled selected></option>
            </select>

            <div class="relative">
              <input
                  type="text" id="section-search-input" readonly autocomplete="off"
                  placeholder="Search or select a section…"
                  class="h-10 w-full cursor-pointer rounded-md border border-neutral-200 px-3 pr-9 text-[15px] text-neutral-900 placeholder:text-neutral-500 transition-colors duration-150 outline-none focus:border-primary focus:cursor-text focus:ring-2 focus:ring-primary/15"
              >
              <svg id="section-chevron" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500 transition-transform duration-150" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            {{-- Positioned via JS (fixed, not absolute) so it isn't clipped by the
                 scrollable form panel — see openDrop() below. --}}
            <div id="section-dropdown" class="fixed z-20 hidden max-h-56 overflow-y-auto rounded-md border border-neutral-200 bg-white shadow-overlay">
              <div class="flex items-center gap-2.5 px-3.5 py-2.5 text-[14px] text-neutral-500">Loading sections&hellip;</div>
            </div>

            @error('section_id')
              <p id="section-error-msg" class="mt-1.5 text-[13px] text-error">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <x-input label="Password" name="password" type="password" id="pw" required placeholder="Create a strong password" oninput="checkStrength()" :error="$errors->first('password')" />
            <div class="mt-1.5">
              <div class="h-[3px] overflow-hidden rounded-full bg-neutral-200">
                <div id="pw-fill" class="h-full w-0 rounded-full transition-all duration-300"></div>
              </div>
              <div id="pw-label" class="mt-1 text-[11px] text-neutral-500">At least 8 characters</div>
            </div>
          </div>

          <x-input label="Confirm password" name="password_confirmation" type="password" id="pw2" required placeholder="Repeat your password" />

          <p class="text-center text-[11px] leading-relaxed text-neutral-500">By creating an account you agree to our Terms of Service and Privacy Policy.</p>

          <x-button type="submit" variant="signup" class="w-full">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>
            Create Account
          </x-button>
        </form>

        <p class="mt-4 text-center text-[13px] text-neutral-500">Already have an account? <a href="{{ route('signin-signin') }}" class="auth-link-signin">Sign in</a></p>
      </div>
    </div>
  </div>

<script>
const roleLabels = { student: 'Sign up as a student for free', teacher: 'Sign up as a teacher' };
const roleRoutes = { student: '{{ route("student.register") }}', teacher: '{{ route("teacher.register") }}' };
const googleRoutes = { student: '{{ route("auth.google.redirect", "student") }}', teacher: '{{ route("auth.google.redirect", "teacher") }}' };
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
  document.getElementById('signupForm').action = roleRoutes[role];
  document.getElementById('section-row').classList.toggle('hidden', role !== 'student');
  document.getElementById('student-id-row').classList.toggle('hidden', role !== 'student');
}

function redirectToGoogle() {
  window.location.href = googleRoutes[currentRole];
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
  // Colors reference the same design tokens used everywhere else (error/warning/primary/success).
  const map = [
    { w: '0%',   bg: '#e5e7eb', text: 'text-neutral-500', txt: 'At least 8 characters' },
    { w: '25%',  bg: '#dc2626', text: 'text-error',       txt: 'Weak' },
    { w: '50%',  bg: '#b45309', text: 'text-warning',     txt: 'Fair' },
    { w: '75%',  bg: '#2563eb', text: 'text-primary',     txt: 'Good' },
    { w: '100%', bg: '#15803d', text: 'text-success',     txt: 'Strong' },
  ];
  const s = map[score];
  fill.style.width = s.w;
  fill.style.background = s.bg;
  label.className = 'mt-1 text-[11px] ' + s.text;
  label.textContent = s.txt;
}

// Validate section selection before form submission
function validateSection(event) {
  if (currentRole !== 'student') {
    return true; // Only validate section/student ID for students
  }

  const studentIdInput = document.getElementById('student_id');
  if (!studentIdInput.value.trim()) {
    event.preventDefault();
    studentIdInput.classList.add('border-error');
    studentIdInput.focus();

    let idErrorMsg = document.getElementById('student-id-error-msg');
    if (!idErrorMsg) {
      idErrorMsg = document.createElement('p');
      idErrorMsg.id = 'student-id-error-msg';
      idErrorMsg.className = 'mt-1.5 text-[13px] text-error';
      document.getElementById('student-id-row').appendChild(idErrorMsg);
    }
    idErrorMsg.textContent = 'Please enter your student ID';

    return false;
  }
  studentIdInput.classList.remove('border-error');
  const idErrorMsg = document.getElementById('student-id-error-msg');
  if (idErrorMsg) idErrorMsg.textContent = '';

  const sectionId = document.getElementById('section_id').value.trim();
  const sectionInput = document.getElementById('section-search-input');

  if (!sectionId) {
    event.preventDefault();

    sectionInput.classList.add('border-error');
    sectionInput.focus();

    let errorMsg = document.getElementById('section-error-msg');
    if (!errorMsg) {
      errorMsg = document.createElement('p');
      errorMsg.id = 'section-error-msg';
      errorMsg.className = 'mt-1.5 text-[13px] text-error';
      document.getElementById('section-row').appendChild(errorMsg);
    }
    errorMsg.textContent = 'Please select a section to continue';

    return false;
  }

  sectionInput.classList.remove('border-error');
  const errorMsg = document.getElementById('section-error-msg');
  if (errorMsg) errorMsg.textContent = '';

  return true;
}

// ═════════════════════════════════════════════════════════════
// SECTION PICKER DROPDOWN
// ═════════════════════════════════════════════════════════════

let sectionPickerState = {
  wrap: null,
  input: null,
  dropdown: null,
  hidden: null,
  chevron: null,
  positionDrop() {
    const rect = this.input.getBoundingClientRect();
    this.dropdown.style.left = rect.left + 'px';
    this.dropdown.style.top = (rect.bottom + 6) + 'px';
    this.dropdown.style.width = rect.width + 'px';
  },
  openDrop() {
    this.positionDrop();
    this.dropdown.classList.remove('hidden');
    this.chevron.style.transform = 'rotate(180deg)';
    this.input.removeAttribute('readonly');
    this.input.focus();
  },
  closeDrop() {
    this.dropdown.classList.add('hidden');
    this.chevron.style.transform = '';
    this.input.setAttribute('readonly', true);
  }
};

function sectionOptionRow(label, index) {
  const opt = document.createElement('div');
  opt.className = 'section-option flex cursor-pointer items-center gap-2.5 border-b border-neutral-100 px-3.5 py-2.5 text-[14px] text-neutral-700 transition-colors duration-150 last:border-b-0 hover:bg-primary-tint hover:text-primary';
  opt.innerHTML = `<span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-primary-tint text-[11px] font-bold text-primary">${index + 1}</span> ${label}`;
  return opt;
}

// Fetch sections from the API on page load and populate both the visible
// dropdown and the hidden <select> that actually submits with the form.
function loadSections() {
  fetch('/api/sections')
    .then(response => response.json())
    .then(data => {
      const dropdown = document.getElementById('section-dropdown');
      const hiddenSelect = document.getElementById('section_id');
      const sections = data.sections || [];

      if (sections.length === 0) {
        dropdown.innerHTML = '<div class="px-3.5 py-2.5 text-[14px] text-neutral-500">No sections available yet</div>';
        return;
      }

      dropdown.innerHTML = '';
      hiddenSelect.innerHTML = '<option value="" disabled selected></option>';

      sections.forEach((section, index) => {
        const selectOption = document.createElement('option');
        selectOption.value = section.id;
        selectOption.textContent = section.name;
        hiddenSelect.appendChild(selectOption);

        const opt = sectionOptionRow(section.name, index);
        opt.dataset.value = section.id;
        opt.dataset.label = section.name;
        dropdown.appendChild(opt);

        opt.addEventListener('click', function() {
          sectionPickerState.hidden.value = this.dataset.value;
          sectionPickerState.input.value = this.dataset.label;
          sectionPickerState.input.classList.remove('border-error');
          const errorMsg = document.getElementById('section-error-msg');
          if (errorMsg) errorMsg.textContent = '';
          sectionPickerState.closeDrop();
        });
      });
    })
    .catch(error => {
      console.error('Error loading sections:', error);
      document.getElementById('section-dropdown').innerHTML = '<div class="px-3.5 py-2.5 text-[14px] text-neutral-500">Error loading sections</div>';
    });
}

function initSectionPicker() {
  sectionPickerState.wrap = document.getElementById('section-row');
  sectionPickerState.input = document.getElementById('section-search-input');
  sectionPickerState.dropdown = document.getElementById('section-dropdown');
  sectionPickerState.hidden = document.getElementById('section_id');
  sectionPickerState.chevron = document.getElementById('section-chevron');

  if (!sectionPickerState.wrap) return;

  sectionPickerState.input.addEventListener('click', () => sectionPickerState.openDrop());
  sectionPickerState.input.addEventListener('keydown', e => {
    if (e.key === 'Escape') { sectionPickerState.closeDrop(); return; }
    if (sectionPickerState.dropdown.classList.contains('hidden')) sectionPickerState.openDrop();
  });

  // The dropdown is position:fixed (so it isn't clipped by the scrollable
  // form panel), which means it won't follow the input if the panel itself
  // scrolls — just close it rather than leaving it visually detached.
  document.getElementById('signup-scroll-panel')?.addEventListener('scroll', () => sectionPickerState.closeDrop());

  sectionPickerState.input.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    sectionPickerState.dropdown.querySelectorAll('.section-option').forEach(opt => {
      const match = opt.dataset.label.toLowerCase().includes(q);
      opt.style.display = match ? '' : 'none';
    });
  });

  document.addEventListener('click', e => {
    if (!sectionPickerState.wrap.contains(e.target)) sectionPickerState.closeDrop();
  });
}

// Set initial form action to match the portal actually visited (was
// previously hardcoded to 'student', so visiting /teacher/register
// directly and submitting without touching a tab first would silently
// submit as a student registration).
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('signupForm').action = roleRoutes[currentRole];
  document.querySelectorAll('[role="tab"]').forEach(t => paintTab(t, t.id === 'tab-' + currentRole));
  document.getElementById('section-row').classList.toggle('hidden', currentRole !== 'student');
  document.getElementById('student-id-row').classList.toggle('hidden', currentRole !== 'student');
  initSectionPicker();
  loadSections();
});
</script>
</body>
</html>

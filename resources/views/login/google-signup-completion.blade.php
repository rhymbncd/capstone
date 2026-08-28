<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Complete Your Profile | Bubog NHS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha384-iw3OoTErCYJJB9mCa8LNS2hbsQ7M3C0EpIsO/H5+EGAkPGc6rk+V8i04oW/K5xq0"
        crossorigin="anonymous" referrerpolicy="no-referrer">
  @vite(['resources/css/app.css', 'resources/js/nav-progress.js'])
</head>
<body class="auth-gradient-bg flex min-h-screen flex-col items-center justify-center p-4 font-sans sm:p-6">

  <a href="{{ route('student.login') }}" class="mb-4 inline-flex w-full max-w-4xl items-center gap-1.5 text-[13px] font-semibold text-white/70 transition-colors duration-150 hover:text-white">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to login
  </a>

  <div class="flex w-full max-w-4xl flex-col overflow-hidden rounded-lg shadow-overlay ring-1 ring-white/10 sm:flex-row">

    <!-- Branding panel -->
    <div class="auth-brand-panel flex flex-col items-center justify-center gap-4 px-6 py-10 text-center sm:flex-[0_0_36%] sm:py-12">
      <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-white/20 bg-[#F7FAFB] sm:h-32 sm:w-32">
        <img
            src="{{ asset('image/587572187-777024998723535-6772324307557000990-n-fotor-20260519155328.png') }}"
            alt="Bubog National High School seal"
            class="h-full w-full object-cover"
            width="354" height="354"
        >
      </div>
      <div class="text-[15px] font-bold tracking-wide text-white/90">Bubog National High School</div>
      <p class="max-w-[220px] text-[13px] leading-relaxed text-white/70">Add your student details so your teachers see you in the right section.</p>
    </div>

    <!-- Form panel -->
    <div class="flex flex-1 items-center justify-center overflow-y-auto bg-white p-6 sm:p-10">
      <div class="w-full max-w-[420px]">
        <h1 class="text-[28px] font-bold tracking-tight text-neutral-900">Complete your profile</h1>
        <p class="mb-6 mt-1 text-[13px] text-neutral-500">One last step to finish your registration.</p>

        @if ($errors->any())
          <x-alert variant="error" class="mb-5">
            @foreach ($errors->all() as $error)
              <p>{{ $error }}</p>
            @endforeach
          </x-alert>
        @endif

        <div class="mb-5 flex items-center gap-3 rounded-md border-l-4 border-[#1b5384] bg-[#eaf1f7] px-4 py-3">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1b5384] text-[15px] font-bold text-white">
            {{ strtoupper(substr($user->name, 0, 1)) }}
          </div>
          <div>
            <div class="text-[11px] font-semibold uppercase tracking-wide text-neutral-500">Signed in as</div>
            <div class="text-[14px] text-neutral-900"><span class="font-bold">{{ $user->name }}</span> <span class="text-neutral-500">({{ $user->email }})</span></div>
          </div>
        </div>

        <form id="completionForm" method="POST" onsubmit="return validateSection(event)">
          @csrf

          <div class="mb-4" id="student-id-row">
            <label for="student_id" class="mb-1.5 block text-[13px] font-medium text-neutral-700">Student ID <span class="text-error">*</span></label>
            <div class="relative">
              <i class="fa-solid fa-id-card pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"></i>
              <input
                type="text" name="student_id" id="student_id" placeholder="e.g. 24-1234" autocomplete="off"
                value="{{ old('student_id') }}"
                class="h-10 w-full rounded-md border border-neutral-200 pl-10 pr-3 text-[15px] text-neutral-900 placeholder:text-neutral-500 outline-none transition-colors duration-150 focus:border-[#1b5384] focus:ring-2 focus:ring-[#1b5384]/15"
              >
            </div>
            @error('student_id')<p class="mt-1.5 text-[13px] text-error">{{ $message }}</p>@enderror
          </div>

          <div class="relative mb-5" id="section-row">
            <label for="section-search-input" class="mb-1.5 block text-[13px] font-medium text-neutral-700">Select your section <span class="text-error">*</span></label>

            {{-- Hidden real select that gets submitted with the form --}}
            <select name="section_id" id="section_id" class="hidden" required>
              <option value="" disabled selected></option>
              @if(isset($sections) && $sections->count() > 0)
                @foreach($sections as $section)
                  <option value="{{ $section->id }}">{{ $section->name }}</option>
                @endforeach
              @endif
            </select>

            <div class="relative flex items-center">
              <i class="fa-solid fa-layer-group pointer-events-none absolute left-3 text-neutral-400"></i>
              <input
                type="text" id="section-search-input" placeholder="Search or select a section…"
                autocomplete="off" readonly
                class="h-10 w-full cursor-pointer rounded-md border border-neutral-200 pl-10 pr-9 text-[15px] text-neutral-900 outline-none transition-colors duration-150 focus:border-[#1b5384] focus:ring-2 focus:ring-[#1b5384]/15"
              >
              <i class="fa-solid fa-chevron-down section-chevron pointer-events-none absolute right-3 text-neutral-400 transition-transform duration-150"></i>
            </div>

            <div class="section-dropdown absolute left-0 right-0 top-[calc(100%+6px)] z-50 hidden max-h-[220px] overflow-y-auto rounded-md border border-neutral-200 bg-white shadow-lg" id="section-dropdown">
              @if(isset($sections) && $sections->count() > 0)
                @foreach($sections as $index => $section)
                  <div
                    class="section-option flex cursor-pointer items-center gap-2.5 border-b border-neutral-100 px-3.5 py-2.5 text-[14px] text-neutral-800 transition-colors duration-150 last:border-b-0 hover:bg-[#eaf1f7] hover:text-[#1b5384]"
                    data-value="{{ $section->id }}"
                    data-label="{{ $section->name }}"
                  >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-[#eaf1f7] text-[11px] font-bold text-[#1b5384]">{{ $index + 1 }}</span>
                    {{ $section->name }}
                  </div>
                @endforeach
              @else
                <div class="section-option flex cursor-default items-center gap-2.5 px-3.5 py-2.5 text-[14px] text-neutral-400">
                  <i class="fa-solid fa-circle-info"></i> No sections available yet
                </div>
              @endif
            </div>

            @error('section_id')
              <p class="mt-1.5 text-[13px] text-error">{{ $message }}</p>
            @enderror
          </div>

          <button type="submit" class="flex h-10 w-full items-center justify-center gap-2 rounded-md bg-[#1b5384] text-[15px] font-bold text-white transition-colors duration-150 hover:bg-[#164468] active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-neutral-300">
            <i class="fa-solid fa-check"></i> Complete registration
          </button>
        </form>

        <p class="mt-4 text-center text-[13px] text-neutral-500">Need help? <a href="{{ route('student.login') }}" class="font-semibold text-[#0f7355] transition-colors duration-150 hover:text-[#0b5c44] hover:underline">Back to login</a></p>
      </div>
    </div>
  </div>

<script>
// Validate section selection before form submission
function validateSection(event) {
  const studentIdInput = document.getElementById('student_id');
  if (!studentIdInput.value.trim()) {
    event.preventDefault();
    studentIdInput.classList.add('border-error', 'focus:border-error');
    studentIdInput.classList.remove('border-neutral-200');
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
  studentIdInput.classList.remove('border-error', 'focus:border-error');
  studentIdInput.classList.add('border-neutral-200');
  const idErrorMsg = document.getElementById('student-id-error-msg');
  if (idErrorMsg) idErrorMsg.textContent = '';

  const sectionId = document.getElementById('section_id').value.trim();
  const sectionInput = document.getElementById('section-search-input');

  if (!sectionId) {
    event.preventDefault();

    sectionInput.classList.add('border-error', 'focus:border-error');
    sectionInput.classList.remove('border-neutral-200');
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

  sectionInput.classList.remove('border-error', 'focus:border-error');
  sectionInput.classList.add('border-neutral-200');
  const errorMsg = document.getElementById('section-error-msg');
  if (errorMsg) errorMsg.textContent = '';

  return true;
}

// ═════════════════════════════════════════════════════════════
// SECTION PICKER DROPDOWN HANDLER
// ═════════════════════════════════════════════════════════════
(function() {
  const wrap = document.getElementById('section-row');
  const input = document.getElementById('section-search-input');
  const dropdown = document.getElementById('section-dropdown');
  const chevron = document.querySelector('.section-chevron');
  const hidden = document.getElementById('section_id');

  if (!wrap) return;

  function openDrop() {
    dropdown.classList.remove('hidden');
    chevron.classList.add('rotate-180');
    input.removeAttribute('readonly');
    input.focus();
  }
  function closeDrop() {
    dropdown.classList.add('hidden');
    chevron.classList.remove('rotate-180');
    input.setAttribute('readonly', true);
  }

  input.addEventListener('click', openDrop);
  input.addEventListener('keydown', e => { if (dropdown.classList.contains('hidden')) openDrop(); });

  input.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    let any = false;
    dropdown.querySelectorAll('.section-option').forEach(opt => {
      if (!opt.dataset.label) return;
      const match = opt.dataset.label.toLowerCase().includes(q);
      opt.style.display = match ? '' : 'none';
      if (match) any = true;
    });
    const noRes = dropdown.querySelector('.section-option:not([data-label])');
    if (noRes) noRes.style.display = any ? 'none' : '';
  });

  dropdown.querySelectorAll('.section-option[data-value]').forEach(opt => {
    opt.addEventListener('click', function() {
      hidden.value = this.dataset.value;
      input.value = this.dataset.label;

      input.classList.remove('border-error', 'focus:border-error');
      input.classList.add('border-neutral-200');
      const errorMsg = document.getElementById('section-error-msg');
      if (errorMsg) errorMsg.textContent = '';

      closeDrop();
    });
  });

  document.addEventListener('click', e => {
    if (!wrap.contains(e.target)) closeDrop();
  });
})();
</script>
</body>
</html>

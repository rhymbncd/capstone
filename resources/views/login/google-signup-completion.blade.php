<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Complete Your Profile | Bubog NHS</title>
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
      max-width: 600px;
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

    .card {
      width: 100%;
      max-width: 600px;
      background: white;
      border-radius: 22px;
      overflow: hidden;
      box-shadow: 0 24px 64px rgba(0,0,0,0.22);
      min-height: auto;
    }

    .card-content {
      padding: 48px 52px;
    }

    h2 {
      font-size: 28px;
      font-weight: 700;
      color: #1a1a1a;
      margin-bottom: 4px;
    }

    .sub {
      font-size: 13px;
      color: #888;
      margin-bottom: 24px;
    }

    .user-info {
      background: #f8fafc;
      border: 1.5px solid #e5e7eb;
      border-radius: 11px;
      padding: 16px 14px;
      margin-bottom: 20px;
      font-size: 14px;
      color: #333;
    }

    .user-label {
      font-size: 12px;
      font-weight: 600;
      color: #888;
      margin-bottom: 4px;
    }

    .ig { margin-bottom: 12px; }
    .ig label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: #555;
      margin-bottom: 6px;
    }

    .section-picker-wrap { position: relative; margin-bottom: 12px; }
    .section-search-box {
      position: relative;
      display: flex;
      align-items: center;
    }
    .section-search-box i.ico {
      position: absolute;
      left: 13px;
      font-size: 15px;
      color: #aaa;
      pointer-events: none;
      z-index: 1;
    }
    .section-search-input {
      width: 100%;
      padding: 11px 13px 11px 40px;
      background: #F1F5F9;
      border: 1.5px solid transparent;
      border-radius: 11px;
      font-size: 14px;
      color: #333;
      outline: none;
      cursor: pointer;
      transition: .2s;
    }
    .section-search-input:focus {
      background: #fff;
      border-color: #1E88E5;
      box-shadow: 0 0 0 3px rgba(30,136,229,0.12);
      cursor: text;
    }
    .section-dropdown {
      position: absolute;
      top: calc(100% + 6px);
      left: 0;
      right: 0;
      background: #fff;
      border: 1.5px solid #e5e7eb;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.10);
      z-index: 100;
      overflow: hidden;
      display: none;
      max-height: 220px;
      overflow-y: auto;
    }
    .section-dropdown.open { display: block; }
    .section-option {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      font-size: 14px;
      color: #333;
      cursor: pointer;
      transition: background .15s;
      border-bottom: 1px solid #f3f4f6;
    }
    .section-option:last-child { border-bottom: none; }
    .section-option:hover, .section-option.focused { background: #EBF5FF; color: #1E88E5; }
    .section-option .badge {
      width: 28px;
      height: 28px;
      border-radius: 8px;
      background: #EBF5FF;
      color: #1E88E5;
      font-size: 11px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .section-option.no-result { color: #aaa; cursor: default; }
    .section-option.no-result:hover { background: none; }
    .section-chevron {
      position: absolute;
      right: 13px;
      font-size: 14px;
      color: #aaa;
      pointer-events: none;
      transition: transform .2s;
    }
    .section-picker-wrap.open .section-chevron { transform: rotate(180deg); }

    .btn-main {
      width: 100%;
      padding: 14px;
      background: #1E88E5;
      color: #fff;
      border: none;
      border-radius: 11px;
      font-size: 16px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      transition: background .2s, transform .1s;
    }
    .btn-main:hover { background: #1565C0; }
    .btn-main:active { transform: scale(0.99); }
    .btn-main:disabled { background: #ccc; cursor: not-allowed; }

    .bottom-link {
      text-align: center;
      font-size: 13px;
      color: #888;
      margin-top: 14px;
    }
    .bottom-link a { color: #1E88E5; font-weight: 600; text-decoration: none; }
    .bottom-link a:hover { text-decoration: underline; }

    @media (max-width: 640px) {
      body { padding: 2vw 2vw; }
      .back-link { font-size: 2.8vw; margin-bottom: 2vw; }
      .card { max-width: 100%; border-radius: 3.5vw; }
      .card-content { padding: 4vw 3.5vw; }
      h2 { font-size: 4.5vw; margin-bottom: 0.5vw; }
      .sub { font-size: 2.8vw; margin-bottom: 3vw; }
      .ig { margin-bottom: 2vw; }
      .btn-main { padding: 2.5vw; font-size: 3vw; }
    }
  </style>
</head>
<body>

<a href="{{ route('student.login') }}" class="back-link">
  <i class="fa-solid fa-arrow-left"></i> Back to Login
</a>

<div class="card">
  <div class="card-content">
    <h2>Complete Your Profile</h2>
    <p class="sub">One last step to finish your registration</p>

    @if ($errors->any())
      <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; border: 1px solid #f5c6cb;">
        @foreach ($errors->all() as $error)
          <p>{{ $error }}</p>
        @endforeach
      </div>
    @endif

    <div class="user-info">
      <div class="user-label">Signed in as</div>
      <div><strong>{{ $user->name }}</strong> ({{ $user->email }})</div>
    </div>

    <form id="completionForm" method="POST" onsubmit="return validateSection(event)">
      @csrf

      <div class="ig" id="student-id-row">
        <label>Student ID <span style="color: #ef4444;">*</span></label>
        <div class="iw">
          <i class="fa-solid fa-id-card ico" style="position:absolute;left:13px;font-size:15px;color:#aaa;pointer-events:none"></i>
          <input type="text" name="student_id" id="student_id" placeholder="e.g. 24-1234" autocomplete="off"
                 style="width:100%;padding:11px 13px 11px 40px;background:#F1F5F9;border:1.5px solid transparent;border-radius:11px;font-size:14px;color:#333;outline:none;transition:.2s"
                 value="{{ old('student_id') }}">
        </div>
        @error('student_id')<p style="color: #ef4444; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
      </div>

      <div class="section-picker-wrap" id="section-row">
        <label for="section-search-input">Select Your Section <span style="color: #ef4444;">*</span></label>

        {{-- Hidden real select that gets submitted with the form --}}
        <select name="section_id" id="section_id" style="display:none" required>
          <option value="" disabled selected></option>
          @if(isset($sections) && $sections->count() > 0)
            @foreach($sections as $section)
              <option value="{{ $section->id }}">{{ $section->name }}</option>
            @endforeach
          @endif
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
          @if(isset($sections) && $sections->count() > 0)
            @foreach($sections as $index => $section)
              <div
                class="section-option"
                data-value="{{ $section->id }}"
                data-label="{{ $section->name }}"
              >
                <span class="badge">{{ $index + 1 }}</span>
                {{ $section->name }}
              </div>
            @endforeach
          @else
            <div class="section-option no-result">
              <i class="fa-solid fa-circle-info" style="color:#ccc"></i>
              No sections available yet
            </div>
          @endif
        </div>

        @error('section_id')
          <p style="color: #ef4444; font-size: 12px; margin-top: 5px;">{{ $message }}</p>
        @enderror
      </div>

      <button type="submit" class="btn-main">
        <i class="fa-solid fa-check"></i> Complete Registration
      </button>
    </form>

    <p class="bottom-link">Need help? <a href="{{ route('student.login') }}">Back to login</a></p>
  </div>
</div>

<script>
// Validate section selection before form submission
function validateSection(event) {
  const studentIdInput = document.getElementById('student_id');
  if (!studentIdInput.value.trim()) {
    event.preventDefault();
    studentIdInput.style.borderColor = '#ef4444';
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

// ═════════════════════════════════════════════════════════════
// SECTION PICKER DROPDOWN HANDLER
// ═════════════════════════════════════════════════════════════
(function() {
  const wrap = document.getElementById('section-row');
  const input = document.getElementById('section-search-input');
  const dropdown = document.getElementById('section-dropdown');
  const hidden = document.getElementById('section_id');

  if (!wrap) return;

  function openDrop() {
    wrap.classList.add('open');
    dropdown.classList.add('open');
    input.removeAttribute('readonly');
    input.focus();
  }
  function closeDrop() {
    wrap.classList.remove('open');
    dropdown.classList.remove('open');
    input.setAttribute('readonly', true);
  }

  input.addEventListener('click', openDrop);
  input.addEventListener('keydown', e => { if (!dropdown.classList.contains('open')) openDrop(); });

  input.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    let any = false;
    dropdown.querySelectorAll('.section-option').forEach(opt => {
      if (opt.classList.contains('no-result')) return;
      const match = opt.dataset.label.toLowerCase().includes(q);
      opt.style.display = match ? '' : 'none';
      if (match) any = true;
    });
    const noRes = dropdown.querySelector('.no-result');
    if (noRes) noRes.style.display = any ? 'none' : '';
  });

  dropdown.querySelectorAll('.section-option:not(.no-result)').forEach(opt => {
    opt.addEventListener('click', function() {
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

  document.addEventListener('click', e => {
    if (!wrap.contains(e.target)) closeDrop();
  });
})();
</script>
</body>
</html>

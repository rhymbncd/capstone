<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Learning Modules</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

  <!-- PDF.js — renders module PDFs inline for viewing (no forced download) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
  <script>
    window.addEventListener('load', function () {
      if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
      }
    });
  </script>
  <style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
:root {
  --blue:#2563eb; --blue-dark:#1d4ed8; --blue-light:#eff6ff; --blue-mid:#60a5fa;
  --green:#10b981; --green-light:#f0fdf4;
  --orange:#f97316; --orange-light:#fff7ed;
  --purple:#a855f7; --purple-light:#faf5ff;
  --red:#ef4444; --red-light:#fef2f2;
  --amber:#f59e0b; --amber-light:#fffbeb;
  --bg:#f4f6fb; --card:#ffffff; --border:#e8ecf2;
  --text:#111827; --text-2:#374151; --text-3:#6b7280; --text-4:#9ca3af;
  --radius:14px; --shadow:0 2px 12px rgba(0,0,0,0.06);
  --lock:#94a3b8;
}
html,body { min-height:100%; font-family:'Plus Jakarta Sans',sans-serif; background:var(--bg); color:var(--text); }

.mq-header {
  position:sticky; top:0; z-index:200;
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 20px;
  background:rgba(255,255,255,0.97); backdrop-filter:blur(12px);
  border-bottom:1px solid var(--border);
}
.mq-back-btn {
  display:inline-flex; align-items:center; gap:6px;
  padding:8px 16px; border-radius:9px; border:1px solid var(--border); background:white;
  font-family:inherit; font-size:13px; font-weight:700; color:var(--text-2); cursor:pointer; transition:all 0.2s;
}
.mq-back-btn:hover { background:var(--blue-light); border-color:var(--blue-mid); color:var(--blue); }
.mq-header-brand { display:flex; align-items:center; gap:8px; font-size:14px; font-weight:700; color:var(--text); }
.mq-logo-icon { width:28px; height:28px; border-radius:7px; background:linear-gradient(135deg,var(--blue-mid),var(--blue)); display:flex; align-items:center; justify-content:center; flex-shrink:0; }

.mq-main { max-width:520px; margin:0 auto; padding:22px 16px 48px; width:100%; }
@media(min-width:640px)  { .mq-main { max-width:720px; padding:28px 24px 48px; } }
@media(min-width:1024px) { .mq-main { max-width:860px; padding:36px 36px 60px; } }

.hero-section { margin-bottom:22px; }
.welcome-title { font-size:22px; font-weight:800; color:var(--text); letter-spacing:-0.5px; line-height:1.2; }
.welcome-subtitle { font-size:13px; color:var(--text-3); margin-top:4px; }

.section-label { font-size:16px; font-weight:800; color:var(--text); letter-spacing:-0.3px; margin-bottom:3px; }
.section-sub   { font-size:12px; color:var(--text-3); margin-bottom:14px; }

.modules-container {
  background:var(--card); border:1px solid var(--border); border-radius:var(--radius);
  padding:18px; margin-bottom:20px; box-shadow:var(--shadow);
  animation:fadeIn 0.4s ease forwards; opacity:0; animation-delay:0.05s;
  transition:box-shadow 0.3s ease, border-color 0.3s ease;
}
.modules-container.mq-module-highlight {
  border-color:var(--blue-mid);
  box-shadow:0 0 0 3px var(--blue-light), var(--shadow);
}
.topic-list { list-style:none; }
.topic-item {
  display:flex; align-items:center; gap:10px;
  padding:11px 14px; border-radius:9px; margin-bottom:6px;
  background:var(--bg); border:1px solid var(--border);
  font-size:13px; font-weight:600; color:var(--text-2);
  cursor:pointer; transition:all 0.15s;
  animation:fadeInUp 0.35s ease forwards; opacity:0; user-select:none;
}
.topic-item:last-child { margin-bottom:0; }
.topic-item:nth-child(1){animation-delay:0.08s} .topic-item:nth-child(2){animation-delay:0.14s}
.topic-item:nth-child(3){animation-delay:0.20s} .topic-item:nth-child(4){animation-delay:0.26s}
.topic-item:nth-child(5){animation-delay:0.32s}
.topic-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; background:var(--border); transition:all 0.2s; }
.topic-item:hover:not(.mq-topic--locked) { background:var(--blue-light); border-color:var(--blue-mid); color:var(--blue); transform:translateX(4px); }
.topic-item:active:not(.mq-topic--locked) { transform:scale(0.98); }
.topic-item:hover:not(.mq-topic--locked) .topic-dot { background:var(--blue-mid); }
.topic-item.mq-topic--done { border-color:var(--green); background:var(--green-light); color:#065f46; }
.topic-item.mq-topic--done .topic-dot { background:var(--green); box-shadow:0 0 0 3px #d1fae5; }
.topic-item.mq-topic--locked { opacity:0.5; cursor:not-allowed; }
.topic-item.mq-topic--locked:hover { transform:none; }
.topic-item.mq-topic--active-unlock { border-color:var(--blue); background:var(--blue-light); color:var(--blue); cursor:pointer; }
.topic-item.mq-topic--active-unlock .topic-dot { background:var(--blue); }
.lock-icon { margin-left:auto; flex-shrink:0; color:var(--lock); font-size:14px; }
@keyframes lockShake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-4px)} 40%{transform:translateX(4px)} 60%{transform:translateX(-3px)} 80%{transform:translateX(3px)} }
.mq-topic--locked.shake { animation:lockShake 0.4s ease; }

/* OVERLAY */
.mq-overlay {
  position:fixed; inset:0; z-index:500;
  background:rgba(0,0,0,0.55); backdrop-filter:blur(6px);
  display:flex; align-items:center; justify-content:center; padding:16px;
  opacity:0; pointer-events:none; transition:opacity 0.25s ease;
}
.mq-overlay.mq-open { opacity:1; pointer-events:all; }
.mq-modal {
  background:var(--card); border:1px solid var(--border); border-radius:20px;
  width:100%; max-width:540px; max-height:88vh; overflow-y:auto; padding:28px 24px;
  position:relative;
  transform:translateY(20px) scale(0.97);
  transition:transform 0.3s cubic-bezier(0.34,1.2,0.64,1);
  box-shadow:0 24px 60px rgba(0,0,0,0.15);
}
.mq-overlay.mq-open .mq-modal { transform:translateY(0) scale(1); }
.mq-modal::-webkit-scrollbar { width:4px; }
.mq-modal::-webkit-scrollbar-thumb { background:var(--border); border-radius:99px; }
.mq-close {
  position:absolute; top:14px; right:14px; width:30px; height:30px; border-radius:50%;
  border:1px solid var(--border); background:var(--bg); color:var(--text-3);
  font-size:15px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.15s;
}
.mq-close:hover { background:var(--red-light); border-color:#fca5a5; color:var(--red); }

.mq-phase-row { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.mq-phase-label { font-size:10px; font-weight:800; letter-spacing:0.14em; text-transform:uppercase; color:var(--blue); }
.mq-badge { padding:2px 10px; border-radius:99px; font-size:10px; font-weight:700; letter-spacing:0.06em; }
.mq-badge--pre    { background:var(--blue-light);   color:var(--blue);   border:1px solid var(--blue-mid); }
.mq-badge--lesson { background:var(--purple-light);  color:var(--purple); border:1px solid #d8b4fe; }
.mq-badge--activity { background:var(--amber-light); color:#92400e;       border:1px solid #fcd34d; }
.mq-badge--post   { background:var(--green-light);   color:var(--green);  border:1px solid #6ee7b7; }
.mq-badge--result { background:var(--green-light);   color:var(--green);  border:1px solid #6ee7b7; }

.mq-steps { display:flex; gap:5px; align-items:center; margin-bottom:18px; }
.mq-step { height:4px; width:22px; border-radius:99px; background:var(--border); transition:all 0.3s ease; }
.mq-step--active { background:var(--blue); width:30px; }
.mq-step--done   { background:var(--green); }

.mq-title { font-size:17px; font-weight:800; color:var(--text); letter-spacing:-0.3px; margin-bottom:5px; }
.mq-desc  { font-size:12px; color:var(--text-3); margin-bottom:20px; line-height:1.55; }

.mq-progress-wrap { margin-bottom:18px; }
.mq-progress-label { font-size:11px; font-weight:700; color:var(--text-3); margin-bottom:5px; text-transform:uppercase; letter-spacing:0.3px; }
.mq-progress-bar { height:5px; background:var(--border); border-radius:99px; overflow:hidden; }
.mq-progress-fill { height:100%; border-radius:99px; transition:width 0.4s ease; background:linear-gradient(90deg,var(--blue-mid),var(--blue)); }
.mq-progress-fill--post { background:linear-gradient(90deg,#34d399,var(--green)); }
.mq-progress-fill--activity { background:linear-gradient(90deg,#fcd34d,var(--amber)); }

/* Timer */
.mq-timer-wrap { display:none; align-items:center; gap:8px; margin-bottom:14px; }
.mq-timer-wrap.mq-timer--visible { display:flex; }
.mq-timer-ring { position:relative; width:42px; height:42px; flex-shrink:0; }
.mq-timer-ring svg { transform:rotate(-90deg); }
.mq-timer-ring circle { fill:none; stroke-width:3.5; }
.mq-timer-ring .ring-bg   { stroke:var(--border); }
.mq-timer-ring .ring-fill { stroke:var(--green); stroke-linecap:round; transition:stroke-dashoffset 1s linear, stroke 0.3s; }
.mq-timer-num { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:var(--text); }
.mq-timer-label { font-size:12px; font-weight:700; color:var(--text-3); }
.mq-timer-label span { font-weight:800; color:var(--text-2); }
.mq-timer-wrap.mq-timer--warn .ring-fill { stroke:var(--orange); }
.mq-timer-wrap.mq-timer--warn .mq-timer-num { color:var(--orange); }
.mq-timer-wrap.mq-timer--danger .ring-fill { stroke:var(--red); }
.mq-timer-wrap.mq-timer--danger .mq-timer-num { color:var(--red); }
.mq-timer-wrap.mq-timer--danger .mq-timer-ring { animation:timerPulse 0.5s ease-in-out infinite alternate; }
@keyframes timerPulse { from{transform:scale(1)} to{transform:scale(1.08)} }

.mq-question-text { font-size:14px; font-weight:700; color:var(--text); line-height:1.55; margin-bottom:16px; }
.mq-choices { display:flex; flex-direction:column; gap:8px; margin-bottom:16px; }
.mq-choice {
  display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px;
  border:1.5px solid var(--border); background:white;
  font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; font-weight:600; color:var(--text-2);
  cursor:pointer; text-align:left; transition:all 0.15s; width:100%;
}
.mq-choice:hover:not(:disabled) { border-color:var(--blue-mid); background:var(--blue-light); color:var(--blue); }
.mq-choice.mq-choice--correct { border-color:var(--green); background:var(--green-light); color:#065f46; }
.mq-choice.mq-choice--wrong   { border-color:var(--red);   background:var(--red-light);   color:#991b1b; }
.mq-choice:disabled { cursor:default; }
.mq-choice-letter { width:26px; height:26px; border-radius:6px; background:var(--border); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0; transition:all 0.15s; }
.mq-choice:hover:not(:disabled) .mq-choice-letter { background:var(--blue); color:white; }
.mq-choice.mq-choice--correct .mq-choice-letter { background:var(--green); color:white; }
.mq-choice.mq-choice--wrong   .mq-choice-letter { background:var(--red);   color:white; }

.mq-feedback { padding:11px 14px; border-radius:9px; font-size:12px; line-height:1.5; margin-bottom:14px; display:none; font-weight:600; }
.mq-feedback.mq-feedback--show    { display:block; }
.mq-feedback.mq-feedback--correct { background:var(--green-light); border:1px solid #6ee7b7; color:#065f46; }
.mq-feedback.mq-feedback--wrong   { background:var(--red-light);   border:1px solid #fca5a5; color:#991b1b; }

.mq-btn-row { display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; }
.mq-btn-row--center { justify-content:center; }
.mq-btn {
  padding:10px 20px; border-radius:9px; font-family:'Plus Jakarta Sans',sans-serif;
  font-size:13px; font-weight:700; cursor:pointer; border:none; transition:all 0.15s;
  display:inline-flex; align-items:center; gap:5px;
}
.mq-btn--primary { background:var(--blue); color:white; }
.mq-btn--primary:hover { background:var(--blue-dark); transform:translateY(-1px); box-shadow:0 4px 14px rgba(37,99,235,0.3); }
.mq-btn--primary:disabled { opacity:0.35; cursor:not-allowed; transform:none !important; box-shadow:none !important; }
.mq-btn--success { background:var(--green); color:white; }
.mq-btn--success:hover { background:#059669; transform:translateY(-1px); box-shadow:0 4px 14px rgba(16,185,129,0.3); }
.mq-btn--success:disabled { opacity:0.35; cursor:not-allowed; transform:none !important; box-shadow:none !important; }
.mq-btn--amber { background:var(--amber); color:white; }
.mq-btn--amber:hover { background:#d97706; transform:translateY(-1px); box-shadow:0 4px 14px rgba(245,158,11,0.3); }
.mq-btn--ghost { background:var(--bg); color:var(--text-3); border:1px solid var(--border); }
.mq-btn--ghost:hover { background:#f3f4f6; color:var(--text-2); }

/* LESSON area */
.mq-lesson-content { background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:18px; margin-bottom:18px; font-size:13px; line-height:1.75; }
.mq-lesson-content h3 { font-size:14px; font-weight:800; color:var(--blue); margin-bottom:10px; }
.mq-lesson-content p  { color:var(--text-2); margin-bottom:8px; }
.mq-formula { font-family:'Courier New',monospace; background:var(--blue-light); border:1px solid var(--blue-mid); border-radius:8px; padding:9px 14px; margin:10px 0; font-size:13px; color:var(--blue-dark); text-align:center; font-weight:700; }
.mq-lesson-content ul { padding-left:18px; color:var(--text-2); }
.mq-lesson-content li { margin-bottom:5px; }

/* Status badges inside lesson */
.mq-status-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.mq-status-pill {
  display:inline-flex; align-items:center; gap:5px;
  padding:5px 12px; border-radius:99px; font-size:11px; font-weight:700;
}
.mq-status-pill--done    { background:var(--green-light); border:1px solid #6ee7b7; color:#065f46; }
.mq-status-pill--pending { background:var(--amber-light);  border:1px solid #fcd34d; color:#92400e; }
.mq-status-pill--locked  { background:#f1f5f9;             border:1px solid #cbd5e1; color:#64748b; }

/* Lesson btn row: activity | view module | post-test */
.mq-lesson-btn-row { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
.mq-btn--view-module {
  background:#ffffff; color:var(--blue); border:1.5px solid var(--blue-mid);
  font-size:12px; padding:10px 14px;
}
.mq-btn--view-module:hover { background:var(--blue-light); border-color:var(--blue); transform:translateY(-1px); box-shadow:0 4px 12px rgba(37,99,235,0.15); }

/* PDF VIEWER — inline viewing of the module PDF, with reading-progress tracking */
.mq-pdf-modal { max-width:720px; height:92vh; max-height:92vh; display:flex; flex-direction:column; overflow-y:hidden; }
.mq-pdf-header { flex-shrink:0; padding-right:30px; margin-bottom:12px; }
.mq-pdf-title { font-size:16px; font-weight:800; color:var(--text); margin-bottom:10px; }
.mq-pdf-progress-label { font-size:11px; font-weight:700; color:var(--text-3); margin-bottom:5px; text-transform:uppercase; letter-spacing:0.3px; }
.mq-pdf-pages {
  flex:1; overflow-y:auto; min-height:0;
  background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:14px;
  display:flex; flex-direction:column; align-items:center; gap:12px;
}
.mq-pdf-page-canvas { max-width:100%; height:auto; box-shadow:0 2px 10px rgba(0,0,0,0.1); border-radius:4px; background:white; }
.mq-pdf-loading, .mq-pdf-error { padding:60px 0; text-align:center; color:var(--text-3); font-size:13px; font-weight:600; }
#mq-pdf-overlay { z-index:600; }

/* ACTIVITY area — fill-in-the-blank */
.mq-activity-box { background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:18px; margin-bottom:16px; }
.mq-activity-instruction { font-size:12px; color:var(--text-3); margin-bottom:14px; line-height:1.5; font-weight:600; }
.mq-activity-item { margin-bottom:16px; }
.mq-activity-q { font-size:13px; font-weight:700; color:var(--text); margin-bottom:8px; line-height:1.5; }
.mq-activity-input {
  width:100%; padding:10px 13px; border-radius:8px;
  border:1.5px solid var(--border); background:white; font-family:'Plus Jakarta Sans',sans-serif;
  font-size:13px; font-weight:600; color:var(--text); transition:border-color 0.15s; outline:none;
}
.mq-activity-input:focus { border-color:var(--blue-mid); }
.mq-activity-input.mq-act--correct { border-color:var(--green); background:var(--green-light); color:#065f46; }
.mq-activity-input.mq-act--wrong   { border-color:var(--red);   background:var(--red-light);   color:#991b1b; }
.mq-activity-hint { font-size:11px; margin-top:5px; font-weight:600; display:none; }
.mq-activity-hint.mq-show { display:block; }
.mq-activity-hint.mq-hint--ok { color:#065f46; }
.mq-activity-hint.mq-hint--err { color:#991b1b; }
.mq-act-score-banner {
  background:var(--green-light); border:1px solid #6ee7b7; border-radius:9px;
  padding:11px 14px; font-size:12px; font-weight:700; color:#065f46;
  margin-bottom:14px; display:none; text-align:center;
}
.mq-act-score-banner.mq-show { display:block; }
.mq-act-fail-banner {
  background:var(--red-light); border:1px solid #fca5a5; border-radius:9px;
  padding:11px 14px; font-size:12px; font-weight:700; color:#991b1b;
  margin-bottom:14px; display:none; text-align:center;
}
.mq-act-fail-banner.mq-show { display:block; }

/* RESULT */
.mq-result-score { text-align:center; padding:20px 0 24px; }
.mq-score-circle { width:100px; height:100px; border-radius:50%; margin:0 auto 14px; display:flex; flex-direction:column; align-items:center; justify-content:center; font-weight:800; }
.mq-score-circle--pass { background:var(--green-light); border:3px solid var(--green); color:var(--green); }
.mq-score-circle--fail { background:var(--red-light);   border:3px solid var(--red);   color:var(--red); }
.mq-score-num { font-size:2rem; line-height:1; }
.mq-score-den { font-size:11px; color:var(--text-3); margin-top:2px; }
.mq-result-msg { font-size:15px; font-weight:800; color:var(--text); margin-bottom:5px; }
.mq-result-sub { font-size:12px; color:var(--text-3); margin-bottom:22px; line-height:1.5; }

@keyframes fadeIn    { from{opacity:0} to{opacity:1} }
@keyframes fadeInUp  { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
@media(max-width:380px) { .mq-main{padding:16px 12px 40px} .welcome-title{font-size:20px} .mq-modal{padding:22px 16px} }

/* SweetAlert2 Custom Styling */
.swal2-modal { border-radius: 18px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
.swal2-icon { border-color: var(--blue-mid); color: var(--blue-mid); }
.swal2-title { color: var(--text); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 18px; font-weight: 700; }
.swal2-html-container { color: var(--text-3); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; }
.swal2-confirm { border-radius: 8px; padding: 8px 24px; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 600; }
  </style>

  <!-- SUPABASE -->
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
  <script>
    window.__ENV__ = {
      SUPABASE_URL:      "{{ config('services.supabase.url') }}",
      SUPABASE_ANON_KEY: "{{ config('services.supabase.anon_key') }}",
    };

    // Expose authenticated user so quiz progress can be tied to a real account
    window.__USER__ = {
      id:    "{{ auth()->user()->id }}",
      name:  "{{ auth()->user()->name }}",
      email: "{{ auth()->user()->email }}",
      role:  "{{ auth()->user()->role ?? 'student' }}",
    };

    // Wait for supabase CDN to load
    window.addEventListener('load', function() {
      if (typeof supabase !== 'undefined') {
        const { createClient } = supabase;
        window.supabaseClient = createClient(window.__ENV__.SUPABASE_URL, window.__ENV__.SUPABASE_ANON_KEY);

        window.getSupabaseClient = function(timeout = 2000) {
            return new Promise((resolve, reject) => {
                if (window.supabaseClient) return resolve(window.supabaseClient);
                const interval = 50; let waited = 0;
                const id = setInterval(() => {
                    if (window.supabaseClient) { clearInterval(id); return resolve(window.supabaseClient); }
                    waited += interval;
                    if (waited >= timeout) { clearInterval(id); return reject(new Error('Supabase client not initialized')); }
                }, interval);
            });
        };
        console.log('✓ Supabase initialized on modules page');
      } else {
        console.error('✗ Supabase CDN failed to load');
      }
    });
  </script>
</head>
<body>

<div id="page-modules">
  <header class="mq-header">
    <button class="mq-back-btn" onclick="history.back()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </button>
    <div class="mq-header-brand">
      <div class="mq-logo-icon">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        </svg>
      </div>
      <span>Math Learning</span>
    </div>
  </header>

  <main class="mq-main">
    <div class="hero-section">
      <h1 class="welcome-title">Learning Modules</h1>
      <p class="welcome-subtitle">Pre-Test → Lesson → Activity → Post-Test · Complete each step to unlock the next</p>
    </div>

    <section class="modules-container" id="module1">
      <div class="section-label">Module 1: Sequences and Series</div>
      <div class="section-sub" id="mod1-sub">0% complete · 0 of 5 topics done</div>
      <ul class="topic-list">
        <li class="topic-item mq-topic--active-unlock" data-topic="ari"><span class="topic-dot"></span>Arithmetic Sequence</li>
        <li class="topic-item mq-topic--locked" data-topic="geo"><span class="topic-dot"></span>Geometric Sequence<span class="lock-icon">🔒</span></li>
        <li class="topic-item mq-topic--locked" data-topic="har"><span class="topic-dot"></span>Harmonic Sequence<span class="lock-icon">🔒</span></li>
        <li class="topic-item mq-topic--locked" data-topic="fib"><span class="topic-dot"></span>Fibonacci Sequence<span class="lock-icon">🔒</span></li>
        <li class="topic-item mq-topic--locked" data-topic="fin"><span class="topic-dot"></span>Finite and Infinite Sequence<span class="lock-icon">🔒</span></li>
      </ul>
    </section>

    <section class="modules-container" id="module2">
      <div class="section-label">Module 2: Polynomials and Polynomial Equations</div>
      <div class="section-sub" id="mod2-sub">0% complete · 0 of 3 topics done</div>
      <ul class="topic-list">
        <li class="topic-item mq-topic--locked" data-topic="div"><span class="topic-dot"></span>Division of Polynomials<span class="lock-icon">🔒</span></li>
        <li class="topic-item mq-topic--locked" data-topic="rem"><span class="topic-dot"></span>Remainder &amp; Factor Theorem<span class="lock-icon">🔒</span></li>
        <li class="topic-item mq-topic--locked" data-topic="poly"><span class="topic-dot"></span>Polynomial Equations<span class="lock-icon">🔒</span></li>
      </ul>
    </section>

    <section class="modules-container" id="module3">
      <div class="section-label">Module 3: Advanced Equations and Functions</div>
      <div class="section-sub" id="mod3-sub">0% complete · 0 of 4 topics done</div>
      <ul class="topic-list">
        <li class="topic-item mq-topic--locked" data-topic="rat"><span class="topic-dot"></span>Rational Equations<span class="lock-icon">🔒</span></li>
        <li class="topic-item mq-topic--locked" data-topic="rad"><span class="topic-dot"></span>Radical Equations<span class="lock-icon">🔒</span></li>
        <li class="topic-item mq-topic--locked" data-topic="exp"><span class="topic-dot"></span>Exponential Functions<span class="lock-icon">🔒</span></li>
        <li class="topic-item mq-topic--locked" data-topic="log"><span class="topic-dot"></span>Logarithmic Functions<span class="lock-icon">🔒</span></li>
      </ul>
    </section>
  </main>
</div>

<!-- MODAL -->
<div class="mq-overlay" id="mq-overlay">
  <div class="mq-modal" id="mq-modal">
    <button class="mq-close" id="mq-close-btn">✕</button>

    <div class="mq-phase-row">
      <span class="mq-phase-label" id="mq-phase-label">PRE-TEST</span>
      <span class="mq-badge mq-badge--pre" id="mq-badge">Pre-Test</span>
    </div>
    <div class="mq-steps" id="mq-steps-row"></div>
    <div class="mq-title" id="mq-title"></div>
    <div class="mq-desc"  id="mq-desc"></div>
    <div class="mq-progress-wrap" id="mq-progress-wrap">
      <div class="mq-progress-label" id="mq-progress-label">Question 1 of 5</div>
      <div class="mq-progress-bar"><div class="mq-progress-fill" id="mq-progress-fill" style="width:0%"></div></div>
    </div>

    <!-- Question area (pre/post) -->
    <div id="mq-question-area">
      <div class="mq-timer-wrap" id="mq-timer-wrap">
        <div class="mq-timer-ring">
          <svg width="42" height="42" viewBox="0 0 42 42">
            <circle class="ring-bg" cx="21" cy="21" r="17"/>
            <circle class="ring-fill" id="mq-ring-fill" cx="21" cy="21" r="17" stroke-dasharray="106.81" stroke-dashoffset="0"/>
          </svg>
          <div class="mq-timer-num" id="mq-timer-num">30</div>
        </div>
        <div class="mq-timer-label">Time left: <span id="mq-timer-text">30</span>s</div>
      </div>
      <div class="mq-question-text" id="mq-question-text"></div>
      <div class="mq-choices"       id="mq-choices"></div>
      <div class="mq-feedback"      id="mq-feedback"></div>
      <div class="mq-btn-row">
        <button class="mq-btn mq-btn--ghost"   id="mq-skip-btn">Skip</button>
        <button class="mq-btn mq-btn--primary"  id="mq-next-btn" disabled>Next →</button>
      </div>
    </div>

    <!-- Lesson area -->
    <div id="mq-lesson-area" style="display:none">
      <div class="mq-status-row" id="mq-status-row"></div>
      <div class="mq-lesson-content" id="mq-lesson-content"></div>
      <div class="mq-lesson-btn-row">
        <button class="mq-btn mq-btn--amber" id="mq-activity-btn">📝 Activity</button>
        <button class="mq-btn mq-btn--view-module" id="mq-viewmodule-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="flex-shrink:0"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
          View Module
        </button>
        <button class="mq-btn mq-btn--success" id="mq-posttest-btn" disabled>Take Post-Test →</button>
      </div>
    </div>

    <!-- Activity area -->
    <div id="mq-activity-area" style="display:none">
      <div class="mq-act-score-banner" id="mq-act-score-banner"></div>
      <div class="mq-act-fail-banner"  id="mq-act-fail-banner"></div>
      <div class="mq-activity-box">
        <div class="mq-activity-instruction" id="mq-act-instruction"></div>
        <div id="mq-act-items"></div>
      </div>
      <div class="mq-btn-row">
        <button class="mq-btn mq-btn--ghost"   id="mq-act-back-btn">← Back to Lesson</button>
        <button class="mq-btn mq-btn--amber"    id="mq-act-submit-btn">Submit Activity</button>
        <button class="mq-btn mq-btn--success"  id="mq-act-proceed-btn" style="display:none">Go to Post-Test →</button>
      </div>
    </div>

    <!-- Result area -->
    <div id="mq-result-area" style="display:none">
      <div class="mq-result-score">
        <div class="mq-score-circle" id="mq-score-circle">
          <span class="mq-score-num" id="mq-score-num">0</span>
          <span class="mq-score-den" id="mq-score-den">/ 5</span>
        </div>
        <div class="mq-result-msg" id="mq-result-msg"></div>
        <div class="mq-result-sub" id="mq-result-sub"></div>
      </div>
      <div class="mq-btn-row mq-btn-row--center" id="mq-result-btns"></div>
    </div>
  </div>
</div>

<!-- PDF VIEWER MODAL -->
<div class="mq-overlay" id="mq-pdf-overlay">
  <div class="mq-modal mq-pdf-modal" id="mq-pdf-modal">
    <button class="mq-close" id="mq-pdf-close-btn">✕</button>
    <div class="mq-pdf-header">
      <div class="mq-pdf-title" id="mq-pdf-title"></div>
      <div class="mq-progress-wrap" style="margin-bottom:0">
        <div class="mq-pdf-progress-label">Read <span id="mq-pdf-progress-pct">0</span>%</div>
        <div class="mq-progress-bar"><div class="mq-progress-fill" id="mq-pdf-progress-fill" style="width:0%"></div></div>
      </div>
    </div>
    <div class="mq-pdf-pages" id="mq-pdf-pages"></div>
  </div>
</div>

<script>
const POST_TIMER_SECS = 30;
const CIRCUMFERENCE   = 2 * Math.PI * 17;
const TOPIC_ORDER = ['ari','geo','har','fib','fin','div','rem','poly','rat','rad','exp','log'];

const stateFlags = {};
TOPIC_ORDER.forEach(k => { stateFlags[k] = { pre:false, activity:false, post:false }; });

function mqGeneric(name) {
  return [
    {q:`What is the primary goal when solving ${name}?`,choices:["Find x","Simplify only","Graph only","Factor only"],ans:0,exp:"The primary goal is to solve for the unknown variable x."},
    {q:`${name} problems require which skill?`,choices:["Only addition","Algebraic manipulation","Pure memorization","Guessing"],ans:1,exp:"Algebraic manipulation is essential."},
    {q:`Which step is usually first in ${name}?`,choices:["Check answer","Identify given info","Draw a graph","Compute derivatives"],ans:1,exp:"Identifying given information is always the first step."},
    {q:`A common error in ${name} is:`,choices:["Using variables","Forgetting extraneous solutions","Writing equations","Using a calculator"],ans:1,exp:"Checking for extraneous solutions is critical."},
    {q:`${name} appear in which real-world context?`,choices:["Engineering & science","Only art","Only history","Only music"],ans:0,exp:"These concepts appear in engineering, science, and economics."},
    {q:`Which operation is central to ${name}?`,choices:["Addition","Multiplication","Equation-solving","Division"],ans:2,exp:"Equation-solving ties all the operations together."},
    {q:`A solution to a ${name} problem must be:`,choices:["Verified","Guessed","Ignored","Rounded"],ans:0,exp:"Every solution must be verified by substituting back."},
    {q:`${name} is part of which branch of math?`,choices:["Geometry","Algebra","Trigonometry","Statistics"],ans:1,exp:"It falls under algebra."},
    {q:`How many solutions can a basic ${name} have?`,choices:["Exactly one","Zero or more","Exactly two","Always three"],ans:1,exp:"Depending on the equation, there may be zero, one, or more solutions."},
    {q:`Variables in ${name} typically represent:`,choices:["Unknown quantities","Fixed constants","Graph axes","Random numbers"],ans:0,exp:"Variables represent unknowns that we solve for."},
    {q:`Which symbol often appears in ${name}?`,choices:["=","<",">","≈"],ans:0,exp:"An equals sign defines the equation to solve."},
    {q:`When is a ${name} considered solved?`,choices:["Variable is isolated","Graph is drawn","Table is made","Formula is cited"],ans:0,exp:"The equation is solved when the variable is isolated."},
    {q:`Extraneous solutions in ${name} arise from:`,choices:["Squaring or multiplying both sides","Addition","Subtraction","Simple substitution"],ans:0,exp:"Operations like squaring can introduce extraneous solutions."},
    {q:`${name} is typically introduced in:`,choices:["Middle/high school algebra","Elementary arithmetic","University calculus","Graduate math"],ans:0,exp:"Standard in secondary algebra curricula."},
    {q:`A key check after solving ${name} is:`,choices:["Substitution into original","Drawing a graph","Making a table","Changing variables"],ans:0,exp:"Always substitute back into the original equation to verify."},
  ];
}

function mkActivity(name, items) { return items; }

const MQ_TOPICS = {
  ari:{
    name:"Arithmetic Sequence",
    lesson:`<h3>📊 Arithmetic Sequence</h3><p>An <strong>arithmetic sequence</strong> is a sequence where the difference between consecutive terms is constant — called the <em>common difference (d)</em>.</p><div class="mq-formula">aₙ = a₁ + (n−1)d</div><p>Where <strong>a₁</strong> is the first term, <strong>d</strong> is the common difference, and <strong>n</strong> is the term number.</p><ul><li>Example: 2, 5, 8, 11, 14 → d = 3</li><li>Example: 10, 7, 4, 1, −2 → d = −3</li></ul><p>Sum of first n terms:</p><div class="mq-formula">Sₙ = n/2 · (2a₁ + (n−1)d)</div>`,
    activity:{
      instruction:"Fill in the blanks. Type your answer in each box (decimals and negatives allowed).",
      items:[
        {q:"The sequence 5, 9, 13, 17, … has a common difference of _____.", ans:"4", hint:"Subtract any term from the next: 9 − 5 = 4"},
        {q:"Using aₙ = a₁ + (n−1)d, the 8th term of 3, 7, 11, … is _____.", ans:"31", hint:"a₈ = 3 + (8−1)·4 = 3 + 28 = 31"},
        {q:"An AP has a₁ = 10 and d = −2. The 5th term is _____.", ans:"2", hint:"a₅ = 10 + (5−1)(−2) = 10 − 8 = 2"},
        {q:"The sum of the first 6 terms of 1, 3, 5, 7, 9, 11 is _____.", ans:"36", hint:"S = 6/2·(1+11) = 3·12 = 36"},
        {q:"How many terms are in the sequence 2, 6, 10, …, 42? Answer: _____.", ans:"11", hint:"42 = 2+(n−1)·4 → n = 11"},
      ]
    },
    pre:[
      {q:"Which is an arithmetic sequence?",choices:["1, 3, 5, 7","2, 4, 8, 16","1, 4, 9, 16","2, 3, 5, 8"],ans:0,exp:"d = 2."},
      {q:"Common difference of 10, 7, 4, 1?",choices:["2","3","-3","4"],ans:2,exp:"7−10 = −3."},
      {q:"5th term of 3, 7, 11, …?",choices:["15","19","23","27"],ans:1,exp:"a₅ = 3+(5−1)·4 = 19."},
      {q:"a₁=2, d=3 → a₄?",choices:["8","11","14","17"],ans:1,exp:"a₄ = 2+3·3 = 11."},
      {q:"Sum of 2,5,8,11,14?",choices:["35","40","45","50"],ans:1,exp:"2+5+8+11+14 = 40."},
      {q:"10th term of 5,10,15,…?",choices:["45","50","55","60"],ans:1,exp:"a₁₀ = 5+9·5 = 50."},
      {q:"d=−4, a₁=20 → a₅?",choices:["4","0","8","−4"],ans:0,exp:"a₅ = 20+4(−4) = 4."},
      {q:"Which formula gives nth term of AP?",choices:["aₙ=a₁·rⁿ⁻¹","aₙ=a₁+(n−1)d","aₙ=a₁/n","aₙ=n²"],ans:1,exp:"aₙ = a₁+(n−1)d."},
      {q:"d if a₁=3, a₆=23?",choices:["4","5","3","6"],ans:0,exp:"5d=20 → d=4."},
      {q:"a₁ if a₄=19, d=5?",choices:["4","9","14","19"],ans:0,exp:"a₁=19−3·5=4."},
    ],
    post:[
      {q:"4th term of 1, 4, 7, …?",choices:["9","10","11","13"],ans:1,exp:"a₄=1+3·3=10."},
      {q:"Common difference of 100,95,90,85?",choices:["5","-5","10","-10"],ans:1,exp:"−5."},
      {q:"a₁=3, d=2 → a₆?",choices:["13","15","17","19"],ans:0,exp:"a₆=3+5·2=13."},
      {q:"Which sequence has d=4?",choices:["1,5,9,13","2,8,14,20","3,6,9,12","5,9,14,20"],ans:0,exp:"1→5→9→13."},
      {q:"10th term of 5,10,15,20,…?",choices:["45","50","55","60"],ans:1,exp:"50."},
      {q:"a₁ if a₅=21, d=4?",choices:["5","6","7","8"],ans:0,exp:"a₁=21−4·4=5."},
      {q:"Sum of 1st 8 terms of 3,6,9,…?",choices:["96","108","120","84"],ans:1,exp:"108."},
      {q:"d for −10,−6,−2,2,…?",choices:["−4","4","6","−6"],ans:1,exp:"4."},
      {q:"Terms in −5,0,5,…,45?",choices:["10","11","12","9"],ans:1,exp:"n=11."},
      {q:"Arithmetic mean of 7 and 13?",choices:["9","10","11","12"],ans:1,exp:"10."},
    ]
  },
  geo:{
    name:"Geometric Sequence",
    lesson:`<h3>📐 Geometric Sequence</h3><p>Each term is multiplied by a fixed <em>common ratio (r)</em>.</p><div class="mq-formula">aₙ = a₁ · r^(n−1)</div><ul><li>2, 6, 18, 54 → r = 3</li><li>100, 50, 25 → r = 0.5</li></ul><div class="mq-formula">Sₙ = a₁(1 − rⁿ) / (1 − r)</div>`,
    activity:{
      instruction:"Fill in the blanks for Geometric Sequence.",
      items:[
        {q:"The common ratio of 3, 9, 27, 81 is _____.",ans:"3",hint:"9÷3 = 3"},
        {q:"The 5th term of 2, 6, 18, … is _____.",ans:"162",hint:"a₅ = 2×3⁴ = 162"},
        {q:"a₁=5, r=2 → a₄ = _____.",ans:"40",hint:"5×2³ = 40"},
        {q:"Geometric mean of 4 and 16 is _____.",ans:"8",hint:"√(4×16) = 8"},
        {q:"Sum of first 4 terms of 1, 2, 4, 8 is _____.",ans:"15",hint:"1+2+4+8 = 15"},
      ]
    },
    pre:mqGeneric("Geometric Sequence"), post:mqGeneric("Geometric Sequence")
  },
  har:{
    name:"Harmonic Sequence",
    lesson:`<h3>🎵 Harmonic Sequence</h3><p>Reciprocals form an arithmetic sequence.</p><div class="mq-formula">HM of a and b = 2ab / (a+b)</div><ul><li>Example: 1, 1/2, 1/3, 1/4 is harmonic</li></ul>`,
    activity:{
      instruction:"Fill in the blanks for Harmonic Sequence.",
      items:[
        {q:"Reciprocals of 1/2, 1/5, 1/8 form an AP with d = _____.",ans:"3",hint:"5−2 = 3"},
        {q:"The 4th term of 1, 1/3, 1/5, … is _____.",ans:"1/7",hint:"Reciprocals: 1,3,5,7"},
        {q:"HM of 3 and 6 is _____.",ans:"4",hint:"2(3)(6)/(3+6) = 36/9 = 4"},
        {q:"HM of 2 and 8 is _____.",ans:"3.2",hint:"2(2)(8)/10 = 3.2"},
        {q:"The 5th term of 1, 1/3, 1/5, … is _____.",ans:"1/9",hint:"Reciprocals: 1,3,5,7,9"},
      ]
    },
    pre:mqGeneric("Harmonic Sequence"), post:mqGeneric("Harmonic Sequence")
  },
  fib:{
    name:"Fibonacci Sequence",
    lesson:`<h3>🌀 Fibonacci Sequence</h3><p>Each term is the sum of the two preceding terms.</p><div class="mq-formula">F(n) = F(n−1) + F(n−2), F(1)=1, F(2)=1</div><p>Sequence: 1, 1, 2, 3, 5, 8, 13, 21, 34, 55, …</p><ul><li>Found in nature: spirals, petals</li><li>Ratio approaches Golden Ratio φ ≈ 1.618</li></ul>`,
    activity:{
      instruction:"Fill in the blanks for Fibonacci Sequence.",
      items:[
        {q:"The next term after 5, 8, 13, 21 is _____.",ans:"34",hint:"21+13 = 34"},
        {q:"F(10) given F(8)=21, F(9)=34 is _____.",ans:"55",hint:"34+21 = 55"},
        {q:"The Golden Ratio φ ≈ _____.",ans:"1.618",hint:"Ratio of consecutive Fibonacci terms"},
        {q:"F(1)+F(2)+F(3)+F(4)+F(5) = _____.",ans:"12",hint:"1+1+2+3+5 = 12"},
        {q:"F(n)=8, F(n−1)=5. Then F(n+1) = _____.",ans:"13",hint:"8+5 = 13"},
      ]
    },
    pre:mqGeneric("Fibonacci Sequence"), post:mqGeneric("Fibonacci Sequence")
  },
  fin:{
    name:"Finite and Infinite Sequence",
    lesson:`<h3>🔢 Finite and Infinite Sequence</h3><p>A <strong>finite sequence</strong> has a last term. An <strong>infinite sequence</strong> goes on forever.</p><div class="mq-formula">Finite: {a₁, a₂, …, aₙ}  |  Infinite: {a₁, a₂, …}</div><ul><li>Finite: 1, 3, 5, 7, 9 (5 terms)</li><li>Infinite: 1, 2, 3, 4, …</li><li>Convergent sequences approach a limit</li></ul>`,
    activity:{
      instruction:"Fill in the blanks about Finite and Infinite Sequences.",
      items:[
        {q:"A sequence that has a last term is called a _____ sequence.",ans:"finite",hint:"It has an endpoint"},
        {q:"A sequence that continues without end is called an _____ sequence.",ans:"infinite",hint:"It has no last term"},
        {q:"The sequence 2, 4, 6, 8 has _____ terms.",ans:"4",hint:"Count them: 2,4,6,8"},
        {q:"1, 2, 3, 4, … is an example of an _____ sequence.",ans:"infinite",hint:"The … means it goes on forever"},
        {q:"A convergent infinite sequence approaches a fixed _____.",ans:"limit",hint:"It gets closer and closer to a value"},
      ]
    },
    pre:mqGeneric("Finite and Infinite Sequence"), post:mqGeneric("Finite and Infinite Sequence")
  },
  div:{
    name:"Division of Polynomials",
    lesson:`<h3>➗ Division of Polynomials</h3><p>Use <strong>Long Division</strong> or <strong>Synthetic Division</strong>.</p><div class="mq-formula">Dividend = Divisor × Quotient + Remainder</div><ul><li>Synthetic Division: shorthand for (x − c)</li><li>Zero remainder = divisor is a factor</li></ul>`,
    activity:{
      instruction:"Fill in the blanks for Division of Polynomials.",
      items:[
        {q:"When dividing x²+3x+2 by (x+1), the remainder is _____.",ans:"0",hint:"f(−1)=1−3+2=0"},
        {q:"Dividing x²−5x+6 by (x−2) gives quotient _____.",ans:"x-3",hint:"(x−2)(x−3)=x²−5x+6"},
        {q:"The degree of the quotient when degree-4 poly is divided by degree-1 is _____.",ans:"3",hint:"4−1=3"},
        {q:"The synthetic value c for dividing by (x+4) is _____.",ans:"-4",hint:"(x+4) = (x−(−4))"},
        {q:"Remainder of x³+1 ÷ (x−1) is _____.",ans:"2",hint:"f(1)=1+1=2"},
      ]
    },
    pre:mqGeneric("Division of Polynomials"), post:mqGeneric("Division of Polynomials")
  },
  rem:{
    name:"Remainder & Factor Theorem",
    lesson:`<h3>📋 Remainder & Factor Theorem</h3><p><strong>Remainder Theorem:</strong> f(x) ÷ (x−c) → remainder = f(c).</p><div class="mq-formula">f(x) ÷ (x − c)  →  Remainder = f(c)</div><p><strong>Factor Theorem:</strong> (x−c) is a factor iff f(c) = 0.</p>`,
    activity:{
      instruction:"Fill in the blanks for the Remainder and Factor Theorems.",
      items:[
        {q:"Remainder of f(x)=x²−4x+3 ÷ (x−1) is _____.",ans:"0",hint:"f(1)=1−4+3=0"},
        {q:"If f(c)=0, then (x−c) is a _____ of f(x).",ans:"factor",hint:"Factor Theorem"},
        {q:"(x+3) is a factor means f(_____) = 0.",ans:"-3",hint:"Set x+3=0 → x=−3"},
        {q:"Remainder of 3x+5 ÷ (x−2) is _____.",ans:"11",hint:"f(2)=6+5=11"},
        {q:"If P(3)=7, remainder of P(x)÷(x−3) is _____.",ans:"7",hint:"Remainder Theorem: remainder = P(c)"},
      ]
    },
    pre:mqGeneric("Remainder and Factor Theorem"), post:mqGeneric("Remainder and Factor Theorem")
  },
  poly:{
    name:"Polynomial Equations",
    lesson:`<h3>📘 Polynomial Equations</h3><p>Set polynomial equal to zero: P(x) = 0.</p><div class="mq-formula">aₙxⁿ + … + a₁x + a₀ = 0</div><ul><li>Degree = max number of roots</li><li>Methods: factoring, quadratic formula, synthetic division</li></ul>`,
    activity:{
      instruction:"Fill in the blanks for Polynomial Equations.",
      items:[
        {q:"A degree-3 polynomial has at most _____ roots.",ans:"3",hint:"Degree = max roots"},
        {q:"x²−5x+6=0 factors as (x−2)(x−_____) = 0.",ans:"3",hint:"2×3=6, 2+3=5"},
        {q:"One root of x²−x−6=0 is x = 3. The other root is x = _____.",ans:"-2",hint:"(x−3)(x+2)=0"},
        {q:"The Fundamental Theorem of Algebra says a degree-n poly has exactly _____ roots in ℂ.",ans:"n",hint:"Counting multiplicity"},
        {q:"If x=2 is a root of P(x), then P(2) = _____.",ans:"0",hint:"Definition of a root"},
      ]
    },
    pre:mqGeneric("Polynomial Equations"), post:mqGeneric("Polynomial Equations")
  },
  rat:{
    name:"Rational Equations",
    lesson:`<h3>⚖️ Rational Equations</h3><p>Contains fractions with polynomial denominators.</p><div class="mq-formula">Multiply both sides by the LCD to clear fractions</div><ul><li>Always check for extraneous solutions (denominators = 0)</li></ul>`,
    activity:{instruction:"Fill in the blanks for Rational Equations.",items:[{q:"To solve a rational equation, multiply both sides by the _____.",ans:"LCD",hint:"Least Common Denominator"},{q:"A solution that makes the denominator zero is called an _____ solution.",ans:"extraneous",hint:"It must be rejected"},{q:"Solving 1/x = 2 gives x = _____.",ans:"0.5",hint:"x = 1/2"},{q:"The LCD of 1/x and 1/(x+1) is _____.",ans:"x(x+1)",hint:"Multiply the two denominators"},{q:"After solving a rational equation, you must always _____ your answer.",ans:"check",hint:"Substitute back into the original"},]},
    pre:mqGeneric("Rational Equations"), post:mqGeneric("Rational Equations")
  },
  rad:{
    name:"Radical Equations",
    lesson:`<h3>√ Radical Equations</h3><p>Contain variables under a radical sign.</p><div class="mq-formula">Isolate the radical → raise both sides to the index power</div><ul><li>Always check for extraneous solutions after squaring</li><li>√(x+3)=4 → x+3=16 → x=13</li></ul>`,
    activity:{instruction:"Fill in the blanks for Radical Equations.",items:[{q:"To eliminate a square root, raise both sides to the power of _____.",ans:"2",hint:"Square both sides"},{q:"Solving √x = 5 gives x = _____.",ans:"25",hint:"5² = 25"},{q:"Solving √(x+3) = 4 gives x = _____.",ans:"13",hint:"x+3=16 → x=13"},{q:"A solution that doesn't satisfy the original equation is called an _____ solution.",ans:"extraneous",hint:"Check by substituting back"},{q:"The index of the radical in ∛x = 2 is _____.",ans:"3",hint:"Cube root = index 3"},]},
    pre:mqGeneric("Radical Equations"), post:mqGeneric("Radical Equations")
  },
  exp:{
    name:"Exponential Functions",
    lesson:`<h3>📈 Exponential Functions</h3><p>f(x) = aˣ where a > 0, a ≠ 1.</p><div class="mq-formula">f(x) = aˣ  |  Inverse: logarithm</div><ul><li>a > 1 → exponential growth</li><li>0 &lt; a &lt; 1 → exponential decay</li></ul>`,
    activity:{instruction:"Fill in the blanks for Exponential Functions.",items:[{q:"f(x) = 2ˣ. Then f(3) = _____.",ans:"8",hint:"2³=8"},{q:"If a > 1 in f(x)=aˣ, the function shows exponential _____.",ans:"growth",hint:"The output increases"},{q:"The inverse of an exponential function is a _____ function.",ans:"logarithmic",hint:"log is the inverse of exp"},{q:"f(x) = (1/2)ˣ shows exponential _____.",ans:"decay",hint:"0 < a < 1"},{q:"2⁰ = _____.",ans:"1",hint:"Any nonzero base to power 0 = 1"},]},
    pre:mqGeneric("Exponential Functions"), post:mqGeneric("Exponential Functions")
  },
  log:{
    name:"Logarithmic Functions",
    lesson:`<h3>📊 Logarithmic Functions</h3><p>Inverse of exponential.</p><div class="mq-formula">log_b(x) = y  ↔  bʸ = x</div><ul><li>Product: log(ab)=log(a)+log(b)</li><li>Quotient: log(a/b)=log(a)−log(b)</li><li>Power: log(aⁿ)=n·log(a)</li></ul>`,
    activity:{instruction:"Fill in the blanks for Logarithmic Functions.",items:[{q:"log₂(8) = _____.",ans:"3",hint:"2³=8"},{q:"log_b(x)=y means b^_____ = x.",ans:"y",hint:"Definition of logarithm"},{q:"log(ab) = log(a) + _____.",ans:"log(b)",hint:"Product rule"},{q:"log(a/b) = log(a) − _____.",ans:"log(b)",hint:"Quotient rule"},{q:"log(aⁿ) = n · _____.",ans:"log(a)",hint:"Power rule"},]},
    pre:mqGeneric("Logarithmic Functions"), post:mqGeneric("Logarithmic Functions")
  },
};

/* ════════════════════════════════════════════════════════════
   FETCH TEACHER QUIZZES FROM SUPABASE
   ════════════════════════════════════════════════════════════ */
const SUPABASE_URL_M      = window.__ENV__?.SUPABASE_URL      ?? '';
const SUPABASE_ANON_KEY_M = window.__ENV__?.SUPABASE_ANON_KEY ?? '';

/**
 * Fetches all rows from quiz_published.
 * Returns an array of { topic_key, pretest, posttest, activity } objects.
 */
async function loadTeacherQuizzes() {
    if (!SUPABASE_URL_M || !SUPABASE_ANON_KEY_M) return [];
    try {
        const res = await fetch(
            `${SUPABASE_URL_M}/rest/v1/quiz_published?select=topic_key,pretest,posttest,activity`,
            {
                headers: {
                    'apikey':        SUPABASE_ANON_KEY_M,
                    'Authorization': `Bearer ${SUPABASE_ANON_KEY_M}`,
                },
            }
        );
        if (!res.ok) return [];
        return await res.json();
    } catch (e) {
        console.warn('Could not load teacher quizzes:', e.message);
        return [];
    }
}

/**
 * Converts teacher question format to MQ_TOPICS format
 */
function teacherQToMQFormat(q) {
    if (!q || !q.options) return null;
    const choices = Object.values(q.options);
    const ansIdx  = Object.keys(q.options).indexOf(q.answer || 'A');
    return {
        q:       q.question || '',
        choices,
        ans:     ansIdx >= 0 ? ansIdx : 0,
        exp:     q.exp || '',
    };
}

/**
 * Converts teacher activity format to MQ_TOPICS activity format
 */
function teacherActToMQFormat(a) {
    return {
        q:    a.question || '',
        ans:  '',           // open-ended — any answer accepted (checked by mqSubmitActivity)
        hint: a.instruction || 'Write your answer based on what you learned.',
    };
}

/**
 * Applies teacher-published quizzes to MQ_TOPICS
 */
function applyTeacherQuizzes(publishedRows) {
    const publishedKeys = new Set();

    if (Array.isArray(publishedRows) && publishedRows.length) {
        publishedRows.forEach(row => {
            const key = row.topic_key;
            if (!key) return;

            const rawPre  = safeParseJSON(row.pretest,  []);
            const rawPost = safeParseJSON(row.posttest, []);
            const rawAct  = safeParseJSON(row.activity, []);

            const pre  = rawPre.map(teacherQToMQFormat).filter(Boolean);
            const post = rawPost.map(teacherQToMQFormat).filter(Boolean);
            const act  = rawAct.map(teacherActToMQFormat).filter(Boolean);

            if (pre.length || post.length || act.length) {
                publishedKeys.add(key);

                if (MQ_TOPICS[key]) {
                    if (pre.length)  MQ_TOPICS[key].pre  = pre;
                    if (post.length) MQ_TOPICS[key].post = post;
                    if (act.length) {
                        MQ_TOPICS[key].activity = {
                            instruction: 'Complete the following activity questions from your teacher.',
                            items:       act,
                        };
                    }
                    console.log(`✓ Applied: ${key}`);
                }
            }
        });
    }

    window._publishedTopicKeys = publishedKeys;

    // Always unlock ari (first topic) regardless of published quizzes
    const firstEl = document.querySelector('.topic-item[data-topic="ari"]');
    if (firstEl && !firstEl.classList.contains('mq-topic--done')) {
        firstEl.classList.remove('mq-topic--locked');
        firstEl.classList.add('mq-topic--active-unlock');
        const lock = firstEl.querySelector('.lock-icon');
        if (lock) lock.remove();
        console.log('🔓 ari always unlocked');
    }
}

/**
 * Load custom topic names from quiz_custom_topics
 */
async function loadAndApplyCustomTopicNames() {
    if (!SUPABASE_URL_M || !SUPABASE_ANON_KEY_M) return;
    try {
        const res = await fetch(
            `${SUPABASE_URL_M}/rest/v1/quiz_custom_topics?select=topic_key,topic_name,module_key`,
            {
                headers: {
                    'apikey':        SUPABASE_ANON_KEY_M,
                    'Authorization': `Bearer ${SUPABASE_ANON_KEY_M}`,
                },
            }
        );
        if (!res.ok) return;
        const rows = await res.json();
        rows.forEach(r => {
            if (MQ_TOPICS[r.topic_key]) {
                MQ_TOPICS[r.topic_key].name = r.topic_name;
            }
        });
    } catch (e) {
        console.warn('Could not load custom topic names:', e.message);
    }
}

/**
 * Safe JSON parse
 */
function safeParseJSON(str, fallback) {
    if (!str) return fallback;
    if (typeof str !== 'string') return str;
    try { return JSON.parse(str); } catch { return fallback; }
}

/**
 * Update module subtitle completion percentages
 */
function updateModuleSubtitles() {
  const setSub = (id, arr) => {
    const done=arr.filter(k=>mqState.completed[k]).length;
    const total=arr.length;
    document.getElementById(id).textContent=Math.round(done/total*100)+'% complete · '+done+' of '+total+' topics done';
  }
  setSub('mod1-sub',['ari','geo','har','fib','fin']);
  setSub('mod2-sub',['div','rem','poly']);
  setSub('mod3-sub',['rat','rad','exp','log']);
}

/**
 * Inject custom topics into page topic lists
 */
function injectCustomTopicsIntoPage() {
    const containerMap = {
        sequences:   '.modules-container:nth-child(1) .topic-list',
        polynomials: '.modules-container:nth-child(2) .topic-list',
        advanced:    '.modules-container:nth-child(3) .topic-list',
    };

    const builtinKeys = new Set(TOPIC_ORDER);

    Object.entries(MQ_TOPICS).forEach(([key, topicData]) => {
        if (builtinKeys.has(key)) return;
        if (document.querySelector(`.topic-item[data-topic="${key}"]`)) return;

        const moduleKey = Object.entries(containerMap).find(([mk]) =>
            topicData.moduleKey === mk
        )?.[0] || 'sequences';

        const list = document.querySelector(containerMap[moduleKey] || containerMap.sequences);
        if (!list) return;

        const li = document.createElement('li');
        li.className = 'topic-item mq-topic--locked';
        li.setAttribute('data-topic', key);
        li.innerHTML = `<span class="topic-dot"></span>${topicData.name || key}<span class="lock-icon">🔒</span>`;
        li.addEventListener('click', function () { openTopic(key); });
        list.appendChild(li);

        console.log(`✓ Injected custom topic "${key}" into page`);
    });
}

const mqState = { topicKey:null, phase:'pre', questions:[], current:0, score:0, answered:false, completed:{}, answersLog:[] };
let mqTimerInterval=null, mqTimeLeft=POST_TIMER_SECS;

/* ════════════════════════════════════════════════════════════
   PROGRESS PERSISTENCE — student_progress table
   Ties each pre/post-test attempt to the authenticated student
   (session_id = user id) so completion survives page reloads.
   ════════════════════════════════════════════════════════════ */

/**
 * Save one pre/post-test attempt for the current student.
 * Upserts on the table's (session_id, topic_key, phase) unique key.
 */
async function mqSaveProgress(topicKey, phase, score, total, passed) {
    if (!SUPABASE_URL_M || !SUPABASE_ANON_KEY_M || !window.__USER__?.id) return;
    try {
        await fetch(
            `${SUPABASE_URL_M}/rest/v1/student_progress?on_conflict=session_id,topic_key,phase`,
            {
                method: 'POST',
                headers: {
                    'apikey':        SUPABASE_ANON_KEY_M,
                    'Authorization': `Bearer ${SUPABASE_ANON_KEY_M}`,
                    'Content-Type':  'application/json',
                    'Prefer':        'resolution=merge-duplicates,return=minimal',
                },
                body: JSON.stringify({
                    session_id:   String(window.__USER__.id),
                    student_name: window.__USER__.name,
                    topic_key:    topicKey,
                    phase,
                    score,
                    total,
                    passed,
                }),
            }
        );
    } catch (e) {
        console.warn('Could not save progress:', e.message);
    }
}

/**
 * Save the actual list of answers a student gave for one pre-test,
 * post-test, or activity attempt, so their teacher can review exactly
 * what was answered (not just the score). Upserts on the same
 * (session_id, topic_key, phase) key as student_progress.
 */
async function mqSaveQuizAnswers(topicKey, phase, answers, score, total) {
    if (!SUPABASE_URL_M || !SUPABASE_ANON_KEY_M || !window.__USER__?.id) return;
    try {
        await fetch(
            `${SUPABASE_URL_M}/rest/v1/student_quiz_answers?on_conflict=session_id,topic_key,phase`,
            {
                method: 'POST',
                headers: {
                    'apikey':        SUPABASE_ANON_KEY_M,
                    'Authorization': `Bearer ${SUPABASE_ANON_KEY_M}`,
                    'Content-Type':  'application/json',
                    'Prefer':        'resolution=merge-duplicates,return=minimal',
                },
                body: JSON.stringify({
                    session_id:   String(window.__USER__.id),
                    student_name: window.__USER__.name,
                    topic_key:    topicKey,
                    phase,
                    answers,
                    score,
                    total,
                }),
            }
        );
    } catch (e) {
        console.warn('Could not save quiz answers:', e.message);
    }
}

/**
 * Load this student's completed topics (passed post-tests) from
 * student_progress and rehydrate mqState.completed + stateFlags.
 */
async function mqLoadProgress() {
    if (!SUPABASE_URL_M || !SUPABASE_ANON_KEY_M || !window.__USER__?.id) return;
    try {
        const res = await fetch(
            `${SUPABASE_URL_M}/rest/v1/student_progress?select=topic_key,phase&session_id=eq.${encodeURIComponent(String(window.__USER__.id))}&phase=eq.post`,
            {
                headers: {
                    'apikey':        SUPABASE_ANON_KEY_M,
                    'Authorization': `Bearer ${SUPABASE_ANON_KEY_M}`,
                },
            }
        );
        if (!res.ok) return;
        const rows = await res.json();
        rows.forEach(r => {
            mqState.completed[r.topic_key] = true;
            if (stateFlags[r.topic_key]) {
                stateFlags[r.topic_key].pre = true;
                stateFlags[r.topic_key].activity = true;
                stateFlags[r.topic_key].post = true;
            }
        });
    } catch (e) {
        console.warn('Could not load progress:', e.message);
    }
}

/** How far (0-100) this student has scrolled through each topic's module PDF. */
const mqReadPct = {};

/**
 * Load this student's saved reading-progress percentage for every topic
 * (stored in student_progress with phase='reading') so the lesson screen
 * can show how much of the PDF they've already read.
 */
async function mqLoadReadingProgress() {
    if (!SUPABASE_URL_M || !SUPABASE_ANON_KEY_M || !window.__USER__?.id) return;
    try {
        const res = await fetch(
            `${SUPABASE_URL_M}/rest/v1/student_progress?select=topic_key,score&session_id=eq.${encodeURIComponent(String(window.__USER__.id))}&phase=eq.reading`,
            {
                headers: {
                    'apikey':        SUPABASE_ANON_KEY_M,
                    'Authorization': `Bearer ${SUPABASE_ANON_KEY_M}`,
                },
            }
        );
        if (!res.ok) return;
        const rows = await res.json();
        rows.forEach(r => { mqReadPct[r.topic_key] = r.score ?? 0; });
    } catch (e) {
        console.warn('Could not load reading progress:', e.message);
    }
}

/**
 * Reflect mqState.completed (loaded from the database) onto the topic
 * list: mark finished topics done and unlock the next topic in order.
 */
function applyCompletedTopicsToUI() {
    Object.keys(mqState.completed).forEach(key => {
        document.querySelectorAll('.topic-item[data-topic="' + key + '"]').forEach(li => {
            li.classList.remove('mq-topic--locked', 'mq-topic--active-unlock');
            li.classList.add('mq-topic--done');
            const lock = li.querySelector('.lock-icon');
            if (lock) lock.remove();
        });
    });

    for (const key of TOPIC_ORDER) {
        if (mqState.completed[key]) continue;
        const el = document.querySelector('.topic-item[data-topic="' + key + '"]');
        if (el && !el.classList.contains('mq-topic--done')) {
            el.classList.remove('mq-topic--locked');
            el.classList.add('mq-topic--active-unlock');
            const lock = el.querySelector('.lock-icon');
            if (lock) lock.remove();
        }
        break;
    }

    updateModuleSubtitles();
}

function mqStartTimer() {
  mqStopTimer(); mqTimeLeft=POST_TIMER_SECS; mqUpdateTimerUI();
  mqTimerInterval = setInterval(() => {
    mqTimeLeft--;
    mqUpdateTimerUI();
    if (mqTimeLeft <= 0) {
      mqStopTimer();
      if (!mqState.answered) {
        const fb=document.getElementById('mq-feedback');
        fb.className='mq-feedback mq-feedback--show mq-feedback--wrong';
        fb.textContent='⏰ Time\'s up!';
        document.querySelectorAll('.mq-choice').forEach(b=>b.disabled=true);
        const q=mqState.questions[mqState.current];
        document.querySelectorAll('.mq-choice')[q.ans].classList.add('mq-choice--correct');
        mqState.answered=true;
        document.getElementById('mq-next-btn').disabled=false;
        document.getElementById('mq-skip-btn').style.display='none';
        setTimeout(mqNext,1500);
      }
    }
  },1000);
}
function mqStopTimer() { if(mqTimerInterval){clearInterval(mqTimerInterval);mqTimerInterval=null;} }
function mqUpdateTimerUI() {
  const num=document.getElementById('mq-timer-num'),text=document.getElementById('mq-timer-text'),ring=document.getElementById('mq-ring-fill'),wrap=document.getElementById('mq-timer-wrap');
  if(!num)return;
  num.textContent=mqTimeLeft; text.textContent=mqTimeLeft;
  ring.style.strokeDashoffset=CIRCUMFERENCE*(1-mqTimeLeft/POST_TIMER_SECS);
  wrap.classList.remove('mq-timer--warn','mq-timer--danger');
  if(mqTimeLeft<=5)wrap.classList.add('mq-timer--danger');
  else if(mqTimeLeft<=10)wrap.classList.add('mq-timer--warn');
}

function openTopic(key) {
    if (!MQ_TOPICS[key]) return;

    const el = document.querySelector('.topic-item[data-topic="' + key + '"]');

    // If locked — shake and return
    if (el && el.classList.contains('mq-topic--locked')) {
        el.classList.remove('shake');
        void el.offsetWidth;
        el.classList.add('shake');
        setTimeout(() => el.classList.remove('shake'), 500);
        return;
    }

    // ALWAYS check if teacher published a quiz — no exceptions
    const publishedKeys = window._publishedTopicKeys || new Set();
    if (!publishedKeys.has(key)) {
        Swal.fire({
            icon:               'info',
            title:              'No Quiz Available Yet',
            html:               `<p style="font-size:13px;color:#6b7280;font-family:'Plus Jakarta Sans',sans-serif">
                                    Your teacher hasn't uploaded a quiz for
                                    <strong style="color:#111827">${MQ_TOPICS[key]?.name || key}</strong> yet.<br><br>
                                    Please check back later.
                                </p>`,
            confirmButtonColor: '#2563eb',
            confirmButtonText:  'Got it',
        });
        return;  // ← STOP here, don't open quiz modal
    }

    // Only open if teacher has published
    mqState.topicKey   = key;
    mqState.phase      = 'pre';
    mqState.current    = 0;
    mqState.score      = 0;
    mqState.answered   = false;
    mqState.answersLog = [];
    mqState.questions  = [...MQ_TOPICS[key].pre];
    mqRender();
    document.getElementById('mq-overlay').classList.add('mq-open');
}
function mqClose() { mqStopTimer(); document.getElementById('mq-overlay').classList.remove('mq-open'); }

function mqBuildSteps(count) {
  const row=document.getElementById('mq-steps-row');
  row.innerHTML='';
  for(let i=0;i<count;i++){
    const d=document.createElement('div');
    d.className='mq-step'; d.id='mq-step-'+i; row.appendChild(d);
  }
}
function mqUpdateSteps() {
  const phases=['pre','lesson','activity','post','result'];
  const idx=phases.indexOf(mqState.phase);
  [0,1,2,3].forEach(i=>{
    const el=document.getElementById('mq-step-'+i);
    if(!el)return;
    el.className='mq-step';
    if(i===idx)el.classList.add('mq-step--active');
    else if(i<idx)el.classList.add('mq-step--done');
  });
}

function mqRender() {
  const topic=MQ_TOPICS[mqState.topicKey];
  mqBuildSteps(4);
  mqUpdateSteps();
  ['mq-question-area','mq-lesson-area','mq-activity-area','mq-result-area'].forEach(id=>document.getElementById(id).style.display='none');
  document.getElementById('mq-progress-wrap').style.display='block';
  document.getElementById('mq-title').textContent=topic.name;

  const phaseLabel=document.getElementById('mq-phase-label');
  const badge=document.getElementById('mq-badge');
  const desc=document.getElementById('mq-desc');
  const fill=document.getElementById('mq-progress-fill');
  const timerWrap=document.getElementById('mq-timer-wrap');

  if(mqState.phase==='pre'){
    phaseLabel.textContent='PRE-TEST'; badge.className='mq-badge mq-badge--pre'; badge.textContent='Pre-Test';
    desc.textContent='Answer these questions to assess your knowledge before the lesson.';
    fill.className='mq-progress-fill'; timerWrap.classList.remove('mq-timer--visible'); mqStopTimer();
    document.getElementById('mq-question-area').style.display='block';
    mqRenderQuestion();
  } else if(mqState.phase==='lesson'){
    phaseLabel.textContent='LESSON'; badge.className='mq-badge mq-badge--lesson'; badge.textContent='Lesson';
    desc.textContent='Study this material carefully before the activity and post-test.';
    document.getElementById('mq-progress-wrap').style.display='none';
    document.getElementById('mq-lesson-content').innerHTML=topic.lesson;
    timerWrap.classList.remove('mq-timer--visible'); mqStopTimer();
    const key=mqState.topicKey;
    const statusRow=document.getElementById('mq-status-row');
    statusRow.innerHTML='';
    function pill(text,cls){const d=document.createElement('div');d.className='mq-status-pill '+cls;d.textContent=text;statusRow.appendChild(d);}
    pill(stateFlags[key].pre?'✅ Pre-Test done':'⏳ Pre-Test done','mq-status-pill--done');
    const readPct=mqReadPct[key]||0;
    pill('📖 '+readPct+'% Read',readPct>=100?'mq-status-pill--done':(readPct>0?'mq-status-pill--pending':'mq-status-pill--locked'));
    pill(stateFlags[key].activity?'✅ Activity done':'🔒 Activity pending',stateFlags[key].activity?'mq-status-pill--done':'mq-status-pill--pending');
    pill(stateFlags[key].post?'✅ Post-Test done':'🔒 Post-Test locked',stateFlags[key].post?'mq-status-pill--done':'mq-status-pill--locked');
    document.getElementById('mq-posttest-btn').disabled=!stateFlags[key].activity;
    document.getElementById('mq-posttest-btn').style.opacity=stateFlags[key].activity?'1':'0.4';
    document.getElementById('mq-lesson-area').style.display='block';
  } else if(mqState.phase==='activity'){
    phaseLabel.textContent='ACTIVITY'; badge.className='mq-badge mq-badge--activity'; badge.textContent='Activity';
    desc.textContent='Complete all items. Get at least 3 out of 5 correct to unlock the Post-Test.';
    fill.className='mq-progress-fill mq-progress-fill--activity';
    document.getElementById('mq-progress-wrap').style.display='none';
    timerWrap.classList.remove('mq-timer--visible'); mqStopTimer();
    mqRenderActivity();
    document.getElementById('mq-activity-area').style.display='block';
  } else if(mqState.phase==='post'){
    phaseLabel.textContent='POST-TEST'; badge.className='mq-badge mq-badge--post'; badge.textContent='Post-Test';
    desc.textContent="Now test what you've learned! You have "+POST_TIMER_SECS+" seconds per question.";
    fill.className='mq-progress-fill mq-progress-fill--post';
    timerWrap.classList.add('mq-timer--visible');
    document.getElementById('mq-question-area').style.display='block';
    mqRenderQuestion();
  } else if(mqState.phase==='result'){
    phaseLabel.textContent='RESULT'; badge.className='mq-badge mq-badge--result'; badge.textContent='Result';
    desc.textContent='Here are your post-test results.';
    document.getElementById('mq-progress-wrap').style.display='none';
    timerWrap.classList.remove('mq-timer--visible'); mqStopTimer();
    mqRenderResult();
    document.getElementById('mq-result-area').style.display='block';
  }
}

function mqRenderQuestion() {
  const q=mqState.questions[mqState.current], total=mqState.questions.length;
  document.getElementById('mq-progress-label').textContent=`Question ${mqState.current+1} of ${total}`;
  document.getElementById('mq-progress-fill').style.width=((mqState.current/total)*100)+'%';
  document.getElementById('mq-question-text').textContent=q.q;
  const choicesEl=document.getElementById('mq-choices');
  choicesEl.innerHTML='';
  ['A','B','C','D'].forEach((letter,i)=>{
    if(i>=q.choices.length)return;
    const btn=document.createElement('button');
    btn.className='mq-choice';
    btn.innerHTML=`<span class="mq-choice-letter">${letter}</span>${q.choices[i]}`;
    btn.addEventListener('click',()=>mqSelectChoice(i));
    choicesEl.appendChild(btn);
  });
  const fb=document.getElementById('mq-feedback');
  fb.className='mq-feedback'; fb.textContent='';
  document.getElementById('mq-next-btn').disabled=true;
  document.getElementById('mq-skip-btn').style.display='';
  mqState.answered=false;
  if(mqState.phase==='post')mqStartTimer();
}

function mqSelectChoice(idx) {
  if(mqState.answered)return;
  mqStopTimer(); mqState.answered=true;
  const q=mqState.questions[mqState.current];
  const btns=document.querySelectorAll('.mq-choice');
  const fb=document.getElementById('mq-feedback');
  btns.forEach(b=>b.disabled=true);
  btns[idx].classList.add(idx===q.ans?'mq-choice--correct':'mq-choice--wrong');
  if(idx!==q.ans)btns[q.ans].classList.add('mq-choice--correct');
  if(idx===q.ans){mqState.score++;fb.className='mq-feedback mq-feedback--show mq-feedback--correct';fb.textContent='✓ Correct! '+q.exp;}
  else{fb.className='mq-feedback mq-feedback--show mq-feedback--wrong';fb.textContent='✗ Incorrect. '+q.exp;}
  mqState.answersLog.push({
    question:  q.q,
    selected:  q.choices[idx] ?? null,
    correct:   q.choices[q.ans] ?? null,
    isCorrect: idx===q.ans,
  });
  document.getElementById('mq-next-btn').disabled=false;
  document.getElementById('mq-skip-btn').style.display='none';
}

function mqNext() {
  mqStopTimer(); mqState.current++;
  if(mqState.current>=mqState.questions.length){
    if(mqState.phase==='pre'){
      stateFlags[mqState.topicKey].pre=true;
      const total=mqState.questions.length;
      mqSaveProgress(mqState.topicKey,'pre',mqState.score,total,mqState.score>=Math.ceil(total*0.6));
      mqSaveQuizAnswers(mqState.topicKey,'pre',mqState.answersLog,mqState.score,total);
      mqState.phase='lesson';
    }
    else{
      mqSaveQuizAnswers(mqState.topicKey,'post',mqState.answersLog,mqState.score,mqState.questions.length);
      mqState.phase='result';mqMarkDone();
    }
    mqRender(); return;
  }
  mqState.answered=false; mqRenderQuestion();
}

function mqStartActivity() {
  mqStopTimer();
  mqState.phase='activity';
  mqRender();
}
function mqStartPost() {
  if(!stateFlags[mqState.topicKey].activity)return;
  mqStopTimer();
  mqState.phase='post'; mqState.current=0; mqState.score=0; mqState.answered=false; mqState.answersLog=[];
  mqState.questions=[...MQ_TOPICS[mqState.topicKey].post];
  mqRender();
}

function mqRenderActivity() {
  const key=mqState.topicKey;
  const act=MQ_TOPICS[key].activity;
  document.getElementById('mq-act-instruction').textContent=act.instruction;
  document.getElementById('mq-act-score-banner').classList.remove('mq-show');
  document.getElementById('mq-act-fail-banner').classList.remove('mq-show');
  document.getElementById('mq-act-proceed-btn').style.display='none';
  document.getElementById('mq-act-submit-btn').style.display='';

  const container=document.getElementById('mq-act-items');
  container.innerHTML='';
  act.items.forEach((item,i)=>{
    const div=document.createElement('div');
    div.className='mq-activity-item';
    div.innerHTML=`<div class="mq-activity-q">${i+1}. ${item.q}</div>
      <input class="mq-activity-input" type="text" id="mq-act-input-${i}" placeholder="Type your answer…" autocomplete="off"/>
      <div class="mq-activity-hint" id="mq-act-hint-${i}"></div>`;
    container.appendChild(div);
  });
}

function mqSubmitActivity() {
  const key = mqState.topicKey;
  const act = MQ_TOPICS[key].activity;
  let correct = 0;
  const activityAnswers = [];

  act.items.forEach((item, i) => {
    const input = document.getElementById('mq-act-input-' + i);
    const hint  = document.getElementById('mq-act-hint-' + i);
    const val   = input.value.trim();
    const ans   = (item.ans ?? '').toString().trim();

    // ── Determine if correct ──────────────────────────────
    let isCorrect = false;

    if (ans === '') {
      // Open-ended (teacher-published activity) — any non-empty answer passes
      isCorrect = val.length > 0;
    } else {
      // Normalize: lowercase, collapse whitespace
      const normalize = s => s.toLowerCase().replace(/\s+/g, ' ').trim();
      const normVal = normalize(val);
      const normAns = normalize(ans);

      if (normVal === normAns) {
        isCorrect = true;
      } else {
        // Numeric comparison — handles "6" vs "6.0", "1/2" vs "0.5", etc.
        const numVal = parseFloat(val.replace(/,/g, ''));
        const numAns = parseFloat(ans.replace(/,/g, ''));
        if (!isNaN(numVal) && !isNaN(numAns)) {
          isCorrect = Math.abs(numVal - numAns) < 0.001;
        }
      }
    }
    // ─────────────────────────────────────────────────────

    if (isCorrect) {
      correct++;
      input.className = 'mq-activity-input mq-act--correct';
      hint.className  = 'mq-activity-hint mq-hint--ok mq-show';
      hint.textContent = ans === '' ? '✓ Answer recorded!' : '✓ Correct!';
    } else {
      input.className = 'mq-activity-input mq-act--wrong';
      hint.className  = 'mq-activity-hint mq-hint--err mq-show';
      hint.textContent = '✗ Hint: ' + (item.hint || '');
    }
    input.disabled = true;

    activityAnswers.push({
      question:  item.q,
      selected:  val,
      correct:   ans === '' ? null : ans,
      isCorrect,
    });
  });

  document.getElementById('mq-act-submit-btn').style.display = 'none';

  // Pass threshold: 60% of items, minimum 1
  const threshold = Math.max(1, Math.ceil(act.items.length * 0.6));
  const pass = correct >= threshold;
  mqSaveQuizAnswers(key, 'activity', activityAnswers, correct, act.items.length);

  if (pass) {
    stateFlags[key].activity = true;
    document.getElementById('mq-act-score-banner').textContent =
      `🎉 You got ${correct}/${act.items.length} correct! Activity passed — Post-Test is now unlocked.`;
    document.getElementById('mq-act-score-banner').classList.add('mq-show');
    document.getElementById('mq-act-proceed-btn').style.display = '';
  } else {
    document.getElementById('mq-act-fail-banner').textContent =
      `You got ${correct}/${act.items.length}. Need at least ${threshold} to pass. Try again!`;
    document.getElementById('mq-act-fail-banner').classList.add('mq-show');
    setTimeout(() => {
      mqRenderActivity();
      document.getElementById('mq-act-fail-banner').classList.remove('mq-show');
    }, 2200);
  }
}

function mqRenderResult() {
  const score=mqState.score, total=mqState.questions.length;
  const pass=score>=Math.ceil(total*0.6);
  const circle=document.getElementById('mq-score-circle');
  circle.className='mq-score-circle '+(pass?'mq-score-circle--pass':'mq-score-circle--fail');
  document.getElementById('mq-score-num').textContent=score;
  document.getElementById('mq-score-den').textContent='/ '+total;
  document.getElementById('mq-result-msg').textContent=pass?'🎉 Excellent Work!':'📚 Keep Practicing!';
  document.getElementById('mq-result-sub').textContent=pass?`You scored ${score}/${total}. Topic marked complete!`:`You scored ${score}/${total}. Review and try again.`;
  const btns=document.getElementById('mq-result-btns');
  btns.innerHTML='';
  if(!pass){
    const retry=document.createElement('button');
    retry.className='mq-btn mq-btn--ghost'; retry.textContent='↩ Review Lesson';
    retry.addEventListener('click',()=>{mqState.phase='lesson';mqRender();});
    btns.appendChild(retry);
  }
  const done=document.createElement('button');
  done.className='mq-btn mq-btn--primary'; done.textContent='✓ Done';
  done.addEventListener('click',mqClose);
  btns.appendChild(done);
}

function mqMarkDone() {
    const key = mqState.topicKey;
    stateFlags[key].post = true;
    mqState.completed[key] = true;

    const total = mqState.questions.length;
    mqSaveProgress(key, 'post', mqState.score, total, mqState.score >= Math.ceil(total * 0.6));

    // Mark current as done
    document.querySelectorAll('.topic-item[data-topic="' + key + '"]').forEach(li => {
        li.classList.remove('mq-topic--locked', 'mq-topic--active-unlock');
        li.classList.add('mq-topic--done');
        const lock = li.querySelector('.lock-icon');
        if (lock) lock.remove();
    });

    // Always unlock the next topic in TOPIC_ORDER
    const idx = TOPIC_ORDER.indexOf(key);
    if (idx !== -1 && idx + 1 < TOPIC_ORDER.length) {
        const nextKey = TOPIC_ORDER[idx + 1];
        const nextEl = document.querySelector('.topic-item[data-topic="' + nextKey + '"]');
        if (nextEl && !nextEl.classList.contains('mq-topic--done')) {
            nextEl.classList.remove('mq-topic--locked');
            nextEl.classList.add('mq-topic--active-unlock');
            const lock = nextEl.querySelector('.lock-icon');
            if (lock) lock.remove();
            console.log(`🔓 Unlocked next: ${nextKey}`);
        }
    }

    updateModuleSubtitles();
}

function updateModuleSubtitles() {
  function setSub(id,arr){
    const done=arr.filter(k=>mqState.completed[k]).length;
    const total=arr.length;
    document.getElementById(id).textContent=Math.round(done/total*100)+'% complete · '+done+' of '+total+' topics done';
  }
  setSub('mod1-sub',['ari','geo','har','fib','fin']);
  setSub('mod2-sub',['div','rem','poly']);
  setSub('mod3-sub',['rat','rad','exp','log']);
}

document.addEventListener('DOMContentLoaded', async function () {

    // ── Wire up topic clicks ──
    document.querySelectorAll('.topic-item[data-topic]').forEach(item => {
        item.addEventListener('click', function () { openTopic(this.getAttribute('data-topic')); });
    });

    // ── Wire up modal buttons ──
    document.getElementById('mq-close-btn').addEventListener('click', mqClose);
    document.getElementById('mq-overlay').addEventListener('click', function (e) { if (e.target === this) mqClose(); });
    document.getElementById('mq-next-btn').addEventListener('click', mqNext);
    document.getElementById('mq-skip-btn').addEventListener('click', mqNext);
    document.getElementById('mq-activity-btn').addEventListener('click', mqStartActivity);
    document.getElementById('mq-viewmodule-btn').addEventListener('click', mqViewModule);
    document.getElementById('mq-pdf-close-btn').addEventListener('click', closePdfViewer);
    document.getElementById('mq-pdf-overlay').addEventListener('click', function (e) { if (e.target === this) closePdfViewer(); });
    document.getElementById('mq-pdf-pages').addEventListener('scroll', mqOnPdfScroll);
    document.getElementById('mq-posttest-btn').addEventListener('click', mqStartPost);
    document.getElementById('mq-act-submit-btn').addEventListener('click', mqSubmitActivity);
    document.getElementById('mq-act-back-btn').addEventListener('click', () => { mqState.phase = 'lesson'; mqRender(); });
    document.getElementById('mq-act-proceed-btn').addEventListener('click', mqStartPost);

    // ────────────────────────────────────────────────────────
    // NEW: Load and apply teacher quizzes before page is usable
    // ────────────────────────────────────────────────────────
    try {
        const [publishedRows] = await Promise.all([
            loadTeacherQuizzes(),
            loadAndApplyCustomTopicNames(),
        ]);

        applyTeacherQuizzes(publishedRows);
        updateModuleSubtitles();
        console.log('✓ Teacher quizzes loaded and applied.');
    } catch (e) {
        console.warn('Teacher quiz load failed, using defaults:', e.message);
        updateModuleSubtitles();
    }

    // ── Inject any custom topics into the topic lists ──
    injectCustomTopicsIntoPage();

    // ── Restore this student's completed topics from Supabase ──
    try {
        await Promise.all([mqLoadProgress(), mqLoadReadingProgress()]);
        applyCompletedTopicsToUI();
    } catch (e) {
        console.warn('Progress restore failed:', e.message);
    }

    // ── Jump straight to the module the student clicked on the dashboard
    //    (e.g. "View Topics" on Module 2), instead of always landing on
    //    Module 1 at the top. Done last, after layout has settled from
    //    the async content above. ──
    if (location.hash) {
        const target = document.querySelector(location.hash);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            target.classList.add('mq-module-highlight');
            setTimeout(() => target.classList.remove('mq-module-highlight'), 1800);
        }
    }
});

/* ── View Module: maps each topic key to its download PDF ── */
const TOPIC_MODULE_FILES = {
  ari:  'Arithmetic Sequence.pdf',
  geo:  'Geometric Sequence.pdf',
  har:  'Harmonic Sequence.pdf',
  fib:  'Fibonacci Sequence.pdf',
  fin:  'Finite and Infinite Sequence.pdf',
  div:  'Division of Polynomials.pdf',
  rem:  'The Remainder and Factor Theorem.pdf',
  poly: 'Polynomial Equation.pdf',
  rat:  'Rational Functions.pdf',
  rad:  'Radical Equations.pdf',
  exp:  'Exponential Functions.pdf',
  log:  'Logarithmic Functions.pdf',
};

/* ════════════════════════════════════════════════════════════
   INLINE PDF VIEWER — opens the module PDF for reading in-page
   (no forced download) and tracks how far the student scrolls
   through it as a reading-progress percentage.
   ════════════════════════════════════════════════════════════ */
const mqPdfState = { topicKey:null, maxScrollPct:0, saveTimer:null };

function mqViewModule() {
  const key  = mqState.topicKey;
  const file = TOPIC_MODULE_FILES[key];
  if (file) openPdfViewer(key, file);
}

async function openPdfViewer(topicKey, filename) {
  const overlay   = document.getElementById('mq-pdf-overlay');
  const pagesEl   = document.getElementById('mq-pdf-pages');
  const titleEl   = document.getElementById('mq-pdf-title');

  titleEl.textContent = MQ_TOPICS[topicKey]?.name || filename;
  pagesEl.innerHTML    = '<div class="mq-pdf-loading">Loading module…</div>';
  overlay.classList.add('mq-open');

  mqPdfState.topicKey     = topicKey;
  mqPdfState.maxScrollPct = mqReadPct[topicKey] || 0;
  updatePdfProgressUI(mqPdfState.maxScrollPct);

  try {
    if (!window.supabaseClient) {
      throw new Error('Supabase client not initialized. Please refresh the page.');
    }
    if (typeof pdfjsLib === 'undefined') {
      throw new Error('PDF viewer failed to load. Check your connection and try again.');
    }

    const sb = await window.getSupabaseClient();
    const { data, error } = await sb.storage.from('materials').download(filename);
    if (error) throw error;

    const arrayBuffer = await data.arrayBuffer();
    const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;

    pagesEl.innerHTML = '';
    for (let i = 1; i <= pdf.numPages; i++) {
      const page     = await pdf.getPage(i);
      const viewport = page.getViewport({ scale: 1.3 });
      const canvas   = document.createElement('canvas');
      canvas.className = 'mq-pdf-page-canvas';
      canvas.width  = viewport.width;
      canvas.height = viewport.height;
      pagesEl.appendChild(canvas);
      await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
    }
  } catch (err) {
    console.error('View module error:', err);
    pagesEl.innerHTML = '<div class="mq-pdf-error">Could not open this module. Please try again.</div>';
    Swal.fire({ icon: 'error', title: 'Could Not Open Module', text: err.message });
  }
}

/** Reading progress = how far down the scrollable PDF container the student has reached. */
function mqOnPdfScroll(e) {
  const el = e.target;
  const scrollable = el.scrollHeight - el.clientHeight;
  const pct = scrollable > 0 ? Math.min(100, Math.round((el.scrollTop / scrollable) * 100)) : 100;

  if (pct > mqPdfState.maxScrollPct) {
    mqPdfState.maxScrollPct = pct;
    updatePdfProgressUI(pct);
    clearTimeout(mqPdfState.saveTimer);
    mqPdfState.saveTimer = setTimeout(() => {
      mqSaveReadingProgress(mqPdfState.topicKey, mqPdfState.maxScrollPct);
    }, 800);
  }
}

function updatePdfProgressUI(pct) {
  document.getElementById('mq-pdf-progress-pct').textContent = pct;
  document.getElementById('mq-pdf-progress-fill').style.width = pct + '%';
}

function closePdfViewer() {
  document.getElementById('mq-pdf-overlay').classList.remove('mq-open');
  document.getElementById('mq-pdf-pages').innerHTML = '';
  clearTimeout(mqPdfState.saveTimer);

  if (mqPdfState.topicKey && mqPdfState.maxScrollPct > (mqReadPct[mqPdfState.topicKey] || 0)) {
    mqReadPct[mqPdfState.topicKey] = mqPdfState.maxScrollPct;
    mqSaveReadingProgress(mqPdfState.topicKey, mqPdfState.maxScrollPct);
  }

  // Refresh the lesson status pills so the updated % Read is visible immediately.
  if (mqState.phase === 'lesson') mqRender();

  mqPdfState.topicKey = null;
  mqPdfState.maxScrollPct = 0;
}

/** Persist this student's reading-progress percentage for a topic (student_progress, phase='reading'). */
async function mqSaveReadingProgress(topicKey, pct) {
  if (!SUPABASE_URL_M || !SUPABASE_ANON_KEY_M || !window.__USER__?.id || !topicKey) return;
  try {
    await fetch(
      `${SUPABASE_URL_M}/rest/v1/student_progress?on_conflict=session_id,topic_key,phase`,
      {
        method: 'POST',
        headers: {
          'apikey':        SUPABASE_ANON_KEY_M,
          'Authorization': `Bearer ${SUPABASE_ANON_KEY_M}`,
          'Content-Type':  'application/json',
          'Prefer':        'resolution=merge-duplicates,return=minimal',
        },
        body: JSON.stringify({
          session_id:   String(window.__USER__.id),
          student_name: window.__USER__.name,
          topic_key:    topicKey,
          phase:        'reading',
          score:        pct,
          total:        100,
          passed:       pct >= 100,
        }),
      }
    );
  } catch (e) {
    console.warn('Could not save reading progress:', e.message);
  }
}

/* ── HARDCODED DOWNLOADS (for the offline materials page) ── */
const HARDCODED_DOWNLOADS = {
  'Module 1: Sequences and Series': [
    { name:'Arithmetic Sequence',          file:'Arithmetic Sequence.pdf',          size:'472 KB' },
    { name:'Geometric Sequence',           file:'Geometric Sequence.pdf',           size:'532 KB' },
    { name:'Harmonic Sequence',            file:'Harmonic Sequence.pdf',            size:'89 KB'  },
    { name:'Fibonacci Sequence',           file:'Fibonacci Sequence.pdf',           size:'70 KB'  },
    { name:'Finite and Infinite Sequence', file:'Finite and Infinite Sequence.pdf', size:'512 KB' },
  ],
  'Module 2: Polynomials': [
    { name:'Division of Polynomials',                  file:'Division of Polynomials.pdf',          size:'514 KB' },
    { name:'The Remainder Theorem and Factor Theorem', file:'The Remainder and Factor Theorem.pdf', size:'577 KB' },
    { name:'Polynomial Equations',                     file:'Polynomial Equation.pdf',              size:'661 KB' },
  ],
  'Module 3: Advanced Equations': [
    { name:'Rational Equations',    file:'Rational Functions.pdf',    size:'1.1 MB' },
    { name:'Radical Equations',     file:'Radical Equations.pdf',     size:'3.9 MB' },
    { name:'Exponential Functions', file:'Exponential Functions.pdf', size:'1.5 MB' },
    { name:'Logarithmic Functions', file:'Logarithmic Functions.pdf', size:'1.3 MB' },
  ],
};
</script>
</body>
</html>
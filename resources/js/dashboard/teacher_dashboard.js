/**
 * teacher_dashboard.js
 * Path: resources/js/dashboard/teacher_dashboard.js
 *
 * Combined: Core dashboard + Quiz Generator
 */

import Swal from 'sweetalert2';

'use strict';

/* ============================================================
   SUPABASE CONFIG
   ============================================================ */
const SUPABASE_URL      = window.__ENV__?.SUPABASE_URL      ?? '';
const SUPABASE_ANON_KEY = window.__ENV__?.SUPABASE_ANON_KEY ?? '';
const BUCKET_NAME    = 'modules';
const STATUS_TABLE   = 'module_status';
const ACTIVITY_TABLE = 'activity_logs';

/* ---------------------------------------------------------------
   Supabase REST helper — read all rows from a table
--------------------------------------------------------------- */
async function sbSelect(table, params = '') {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}${params}`, {
        headers: {
            'apikey':        SUPABASE_ANON_KEY,
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            'Content-Type':  'application/json',
        },
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB read failed (${res.status})`);
    }
    return res.json();
}

/* ---------------------------------------------------------------
   Supabase REST helper — insert a row
--------------------------------------------------------------- */
async function sbInsert(table, data) {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}`, {
        method: 'POST',
        headers: {
            'apikey':        SUPABASE_ANON_KEY,
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            'Content-Type':  'application/json',
            // Tables like activity_logs are intentionally locked down to
            // INSERT-only for the anon key (no SELECT), so this must not ask
            // PostgREST to hand the row back — that requires SELECT too.
            'Prefer':        'return=minimal',
        },
        body: JSON.stringify(data),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB insert failed (${res.status})`);
    }
    const text = await res.text();
    return text ? JSON.parse(text) : null;
}

/* ---------------------------------------------------------------
   Supabase REST helper — upsert rows (insert or update on conflict)
--------------------------------------------------------------- */
async function sbUpsert(table, data) {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}`, {
        method: 'POST',
        headers: {
            'apikey':        SUPABASE_ANON_KEY,
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            'Content-Type':  'application/json',
            'Prefer':        'resolution=merge-duplicates,return=representation',
        },
        body: JSON.stringify(data),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB upsert failed (${res.status})`);
    }
    return res.json();
}

/* ---------------------------------------------------------------
   Supabase REST helper — update rows by ID (PATCH)
--------------------------------------------------------------- */
async function sbUpdate(table, id, data) {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}?id=eq.${id}`, {
        method: 'PATCH',
        headers: {
            'apikey':        SUPABASE_ANON_KEY,
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            'Content-Type':  'application/json',
            'Prefer':        'return=representation',
        },
        body: JSON.stringify(data),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB update failed (${res.status})`);
    }
    return res.json();
}

/* ---------------------------------------------------------------
   Supabase REST helper — delete rows by ID
--------------------------------------------------------------- */
async function sbDelete(table, id) {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}?id=eq.${id}`, {
        method: 'DELETE',
        headers: {
            'apikey':        SUPABASE_ANON_KEY,
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            'Content-Type':  'application/json',
        },
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB delete failed (${res.status})`);
    }
    return true;
}

/* ---------------------------------------------------------------
   fetchModulesFromSupabase
   1. List all files in the "modules" bucket
   2. Fetch all rows from module_status table
   3. Merge: status from DB drives the badge shown to the teacher
      - 'approved'  → "Published" (green)
      - 'rejected'  → "Rejected"  (red)
      - 'pending'   → "Pending Review" (orange) — waiting for admin
   4. Auto-insert a "pending" row for any new file not yet in DB
--------------------------------------------------------------- */
async function fetchModulesFromSupabase() {
    // 1. List bucket files
    const listRes = await fetch(`${SUPABASE_URL}/storage/v1/object/list/${BUCKET_NAME}`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            'Content-Type':  'application/json',
        },
        body: JSON.stringify({ prefix: '', limit: 200, offset: 0 }),
    });
    if (!listRes.ok) {
        const err = await listRes.json().catch(() => ({}));
        throw new Error(err.message || `Failed to list bucket (${listRes.status})`);
    }
    const files = await listRes.json();

    // 2. Fetch status rows
    let statusRows = [];
    try {
        statusRows = await sbSelect(STATUS_TABLE, '?select=*');
    } catch (e) {
        console.warn('Could not fetch module_status table:', e.message);
    }

    // 3. Build lookup map: storageName → DB row
    const statusMap = {};
    statusRows.forEach(r => { statusMap[r.file_name] = r; });

    // 4. Merge files + status
    const merged = files
        .filter(f => f.name && !f.name.startsWith('.'))
        .map((f, idx) => {
            const publicUrl   = `${SUPABASE_URL}/storage/v1/object/public/${BUCKET_NAME}/${encodeURIComponent(f.name)}`;
            const rawName     = f.name.replace(/^\d+_/, '');
            const fileTitle   = rawName.replace(/\.[^/.]+$/, '');
            const existing    = statusMap[f.name];
            const dbStatus    = existing?.status ?? 'pending';

            // Use saved title/topic/desc from DB if available, otherwise use defaults
            const title       = existing?.module_title ?? fileTitle;
            const topic       = existing?.module_topic ?? guessTopic(fileTitle);
            const desc        = existing?.module_desc ?? '';

            // Map admin DB status → teacher-visible label
            const displayStatus =
                dbStatus === 'approved' ? 'Published' :
                dbStatus === 'rejected' ? 'Rejected'  :
                'Pending Review';

            return {
                id:          f.id || idx + 1,
                storageName: f.name,
                title,
                desc,
                topic,
                status:      displayStatus,
                dbStatus,                          // raw value for logic checks
                completion:  0,
                date:        formatDate(f.created_at || f.updated_at),
                fileName:    rawName,
                fileSize:    f.metadata?.size ?? 0,
                fileUrl:     publicUrl,
                dbId:        existing?.id ?? null,
            };
        });

    // 5. Auto-insert pending rows for files with no DB record yet
    const toInsert = merged
        .filter(m => !m.dbId)
        .map(m => ({
            file_name: m.storageName,
            file_url:  m.fileUrl,
            status:    'pending',
        }));

    if (toInsert.length) {
        try {
            const inserted = await sbUpsert(STATUS_TABLE, toInsert);
            if (Array.isArray(inserted)) {
                inserted.forEach(row => {
                    const item = merged.find(m => m.storageName === row.file_name);
                    if (item) item.dbId = row.id;
                });
            }
        } catch (e) {
            console.warn('Auto-insert status rows failed:', e.message);
        }
    }

    return merged;
}

/** Guess the module group from the file title keywords */
function guessTopic(title) {
    const t = title.toLowerCase();
    if (/arithmetic|geometric|harmonic|fibonacci|finite|infinite|sequence|series/.test(t))
        return 'Module 1: Sequences and Series';
    if (/polynomial|remainder|factor|division/.test(t))
        return 'Module 2: Polynomials';
    if (/rational|radical|exponential|logarithm|system/.test(t))
        return 'Module 3: Advanced Equations';
    return 'General';
}

/* ---------------------------------------------------------------
   Upload a new file to Supabase Storage.
   Returns { publicUrl, path } on success, throws on failure.
--------------------------------------------------------------- */
async function uploadFileToSupabase(file) {
    const safeFileName = file.name.replace(/[^a-zA-Z0-9.\-_]/g, '_');
    const filePath     = `${Date.now()}_${safeFileName}`;
    const uploadUrl    = `${SUPABASE_URL}/storage/v1/object/${BUCKET_NAME}/${filePath}`;

    const res = await fetch(uploadUrl, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            'Content-Type':  file.type || 'application/octet-stream',
            'x-upsert':      'false',
        },
        body: file,
    });

    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `Upload failed (${res.status})`);
    }

    const publicUrl = `${SUPABASE_URL}/storage/v1/object/public/${BUCKET_NAME}/${filePath}`;
    return { publicUrl, path: filePath };
}

/* ============================================================
   DELETE FILE FROM SUPABASE STORAGE
   ============================================================ */
async function deleteFileFromSupabase(filePath) {
    const deleteUrl = `${SUPABASE_URL}/storage/v1/object/${BUCKET_NAME}/${filePath}`;
    
    const res = await fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
        },
    });

    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `Delete failed (${res.status})`);
    }
    
    return true;
}

/* ============================================================
   SECURITY — XSS Prevention
   ============================================================ */
const Security = {
    escape(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g,  '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#x27;')
            .replace(/\//g, '&#x2F;')
            .replace(/`/g,  '&#x60;');
    },
    sanitize(str) {
        if (str == null) return '';
        return String(str).replace(/[<>"'`]/g, '').trim();
    },
    isValidStatus(s) { return ['Excellent','Good','Average','Needs Help'].includes(s); },
};

/* ============================================================
   STATE
   ============================================================ */
let students    = [];

/** Fetch helper for Laravel-backed endpoints (CSRF + JSON, same-origin). */
async function apiFetch(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            'Accept': 'application/json',
            ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...options.headers,
        },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || `Request failed (${res.status})`);
    return data;
}

/**
 * Load the authenticated teacher's students (with real completion
 * progress computed server-side from student_progress).
 */
async function loadStudents() {
    try {
        const data = await pollJson('/teacher/students');
        if (data !== null) {
            // data === null means 304 Not Modified — students/subjectCompletion
            // are already correct from the last successful load, leave them be.
            students = Array.isArray(data.students) ? data.students : [];
            subjectCompletion = Array.isArray(data.subjectCompletion) ? data.subjectCompletion : [];
        }
    } catch (e) {
        // Keep showing whatever was last successfully loaded rather than
        // wiping the roster to empty over one failed poll tick.
        console.warn('Could not load students:', e.message);
    }
}
let subjectCompletion = [];
let feedbacks   = [];
let activity    = [];
let modulesData = [];
let sections    = [];
let allSections = [];  // Sections for Reports page display

let feedbackTargetId = null;
let pendingFile      = null;

const STUDENTS_PER_PAGE = 6;
let studentPage = 1;

/* ============================================================
   QUIZ STATE
   ============================================================ */
let currentQuiz  = null;   // { topic, activity, grade, difficulty, pretest:[], posttest:[], activity:[] }
let savedQuizzes = [];     // loaded from Supabase on page visit

/* ============================================================
   NAVIGATION
   ============================================================ */
function navigate(page) {
    const allowed = ['home', 'students', 'progress', 'reports', 'modules', 'profile', 'quiz'];
    if (!allowed.includes(page)) return;

    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidebar-item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));

    document.getElementById('page-' + page).classList.add('active');
    document.querySelectorAll(`[data-page="${page}"]`).forEach(b => b.classList.add('active'));
    window.scrollTo(0, 0);

    if (page === 'home')     Promise.all([loadStudents(), loadFeedbacks()]).then(renderHome);
    if (page === 'students') loadStudents().then(renderStudents);
    if (page === 'progress') loadStudents().then(renderProgress);
    if (page === 'reports')  {
        loadSectionsForReports();
        Promise.all([loadStudents(), loadFeedbacks()]).then(renderReports);
    }
    if (page === 'modules')  loadAndRenderModules();  // always re-fetches
    if (page === 'profile')  renderProfile();
    if (page === 'quiz')     initQuizPage();           // init quiz on visit
}

/* ============================================================
   HOME
   ============================================================ */
function renderHome() {
    setText('m-total',    students.length);
    setText('m-avg',      avgProgress() + '%');
    setText('m-pending',  feedbacks.filter(f => !f.read).length);
    setText('m-messages', 0);

    const listEl = document.getElementById('home-student-list');
    if (!students.length) {
        listEl.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">👩‍🎓</div>
                <h4>No students yet</h4>
                <p>Students will appear here once they enroll in your class.</p>
            </div>`;
        return;
    }

    listEl.innerHTML = students.slice(0, 4).map(s => `
        <div class="student-item" onclick="navigate('students')">
            <div class="student-info">
                <div class="student-avatar">${Security.escape(initials(s.name))}</div>
                <div>
                    <div class="student-name">${Security.escape(s.name)}</div>
                    <div class="student-meta">Progress: ${Security.escape(String(s.progress))}%</div>
                </div>
            </div>
            <span class="status-badge ${badgeClass(s.status)}">${Security.escape(s.status)}</span>
        </div>`).join('');
}

/* ============================================================
   STUDENTS
   ============================================================ */
function getFilteredStudents() {
    const q      = Security.sanitize(document.getElementById('student-search')?.value || '').toLowerCase();
    const status = document.getElementById('student-status-filter')?.value || '';
    return students.filter(s =>
        s.name.toLowerCase().includes(q) &&
        (!status || s.status === status)
    );
}

function renderStudents() {
    setText('s-total',     students.length);
    setText('s-avg',       avgProgress() + '%');
    setText('s-help',      students.filter(s => s.status === 'Needs Help').length);
    setText('s-excellent', students.filter(s => s.status === 'Excellent').length);

    const filtered   = getFilteredStudents();
    const totalPages = Math.max(1, Math.ceil(filtered.length / STUDENTS_PER_PAGE));
    if (studentPage > totalPages) studentPage = totalPages;
    const slice = filtered.slice((studentPage - 1) * STUDENTS_PER_PAGE, studentPage * STUDENTS_PER_PAGE);

    const tbody = document.getElementById('students-tbody');
    if (!slice.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><div class="empty-icon">👤</div><h4>No students found</h4><p>Try a different search or filter.</p></div></td></tr>`;
    } else {
        tbody.innerHTML = slice.map((s, i) => `
            <tr>
                <td style="color:var(--text-4);font-size:12px">${(studentPage - 1) * STUDENTS_PER_PAGE + i + 1}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="student-avatar" style="width:30px;height:30px;font-size:10px">${Security.escape(initials(s.name))}</div>
                        <b>${Security.escape(s.name)}</b>
                    </div>
                </td>
                <td style="font-size:12px;color:var(--text-3)">${s.studentId ? Security.escape(s.studentId) : '—'}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="progress-bar" style="width:80px;height:6px;display:inline-block">
                            <div class="progress-fill" style="width:${Security.escape(String(s.progress))}%;background:${progressColor(s.progress)}"></div>
                        </div>
                        <span style="font-size:12px;font-weight:700;color:var(--text-2)">${Security.escape(String(s.progress))}%</span>
                    </div>
                </td>
                <td><span class="status-badge ${badgeClass(s.status)}">${Security.escape(s.status)}</span></td>
                <td style="font-size:12px;color:var(--text-3)">${Security.escape(s.lastActive)}</td>
                <td>
                    <button class="tbl-btn view"     onclick="viewStudent(${s.id})">View</button>
                    <button class="tbl-btn feedback" onclick="openFeedback(${s.id})">Feedback</button>
                </td>
            </tr>`).join('');
    }

    const pg = document.getElementById('student-pagination');
    pg.innerHTML = '';
    pg.appendChild(makePgBtn('‹ Prev', studentPage === 1, () => { studentPage--; renderStudents(); }));
    for (let i = 1; i <= totalPages; i++) {
        const btn = makePgBtn(i, false, () => { studentPage = i; renderStudents(); });
        if (i === studentPage) btn.classList.add('active');
        pg.appendChild(btn);
    }
    pg.appendChild(makePgBtn('Next ›', studentPage === totalPages, () => { studentPage++; renderStudents(); }));
}

function filterStudents() { studentPage = 1; renderStudents(); }

function viewStudent(id) {
    const s = students.find(x => x.id === id);
    if (!s) return;
    Swal.fire({
        title: Security.escape(s.name),
        html: `
            <div style="text-align:left;font-family:'Plus Jakarta Sans',sans-serif">
                <p style="margin-bottom:8px;color:#6b7280;font-size:13px">Student Details</p>
                <div style="background:#f4f6fb;border-radius:10px;padding:14px;margin-bottom:12px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                        <span style="font-size:12px;color:#6b7280;font-weight:600">Progress</span>
                        <span style="font-size:13px;font-weight:800;color:#111827">${Security.escape(String(s.progress))}%</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                        <span style="font-size:12px;color:#6b7280;font-weight:600">Status</span>
                        <span style="font-size:12px;font-weight:700">${Security.escape(s.status)}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:12px;color:#6b7280;font-weight:600">Last Active</span>
                        <span style="font-size:12px;color:#9ca3af">${Security.escape(s.lastActive)}</span>
                    </div>
                </div>
                <p style="font-size:12px;color:#6b7280">${Security.escape(s.notes || 'No additional notes.')}</p>
            </div>`,
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'Send Feedback',
        showCancelButton: true,
        cancelButtonText: 'Close',
    }).then(r => { if (r.isConfirmed) openFeedback(id); });
}

/* ============================================================
   FEEDBACK
   ============================================================ */

/** Load real feedback this teacher has sent (from the database). */
let lastFeedbacksPayload = null;

async function loadFeedbacks() {
    try {
        const data = await pollJson('/teacher/feedback');
        if (data !== null) {
            lastFeedbacksPayload = data;
        }
        // data === null means 304 Not Modified — lastFeedbacksPayload is already current.
    } catch (e) {
        console.warn('Could not load feedback:', e.message);
        if (!lastFeedbacksPayload) {
            feedbacks = [];
        }
        return; // keep showing whatever was last loaded rather than clearing the UI
    }

    feedbacks = Array.isArray(lastFeedbacksPayload.feedback) ? lastFeedbacksPayload.feedback : [];
}

const FEEDBACK_TYPE_META = {
    encouragement: { icon: '💪', label: 'Encouragement' },
    improvement:   { icon: '📈', label: 'Needs Improvement' },
    praise:        { icon: '🌟', label: 'Praise' },
    reminder:      { icon: '⏰', label: 'Reminder' },
};

/** Render this student's full feedback history inside the Send Feedback modal. */
function renderFeedbackHistory(studentId) {
    const wrap = document.getElementById('fb-history-wrap');
    const list = document.getElementById('fb-history-list');
    if (!wrap || !list) return;

    const history = feedbacks.filter(f => f.studentId === studentId);

    if (!history.length) {
        wrap.style.display = 'none';
        return;
    }

    wrap.style.display = '';
    list.innerHTML = history.map(f => {
        const meta = FEEDBACK_TYPE_META[f.type] || { icon: '💬', label: f.type };
        return `
            <div style="background:#f4f6fb;border-radius:8px;padding:10px 12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <span style="font-size:11px;font-weight:700;color:#2563eb">${meta.icon} ${Security.escape(meta.label)}</span>
                    <span style="font-size:10px;color:#9ca3af">${Security.escape(f.date)}</span>
                </div>
                <div style="font-size:12.5px;color:#374151">${Security.escape(f.message)}</div>
            </div>`;
    }).join('');
}

const ANSWERS_PHASE_META = {
    pre:      { icon: '📝', label: 'Pre-Test' },
    post:     { icon: '✅', label: 'Post-Test' },
    activity: { icon: '📋', label: 'Activity' },
};

/** Show a student's saved pre-test/post-test/activity answers, fetched from the backend. */
async function viewStudentAnswers(studentId, studentName) {
    Swal.fire({
        title: `${studentName}'s Answers`,
        html: '<p style="color:#9ca3af;font-size:13px;padding:20px 0">Loading…</p>',
        width: 620,
        showConfirmButton: false,
        showCloseButton: true,
    });

    let attempts;
    try {
        const data = await apiFetch(`/teacher/students/${studentId}/answers`);
        attempts = data.attempts || [];
    } catch (err) {
        Swal.update({ html: `<p style="color:#ef4444;font-size:13px;padding:20px 0">Could not load answers: ${Security.escape(err.message)}</p>` });
        return;
    }

    if (!attempts.length) {
        Swal.update({ html: '<p style="color:#9ca3af;font-size:13px;padding:20px 0">This student hasn\'t attempted any pre-test, post-test, or activity yet.</p>' });
        return;
    }

    const html = `
        <div style="text-align:left;max-height:60vh;overflow-y:auto;padding:4px 2px">
            ${attempts.map(a => {
                const meta = ANSWERS_PHASE_META[a.phase] || { icon: '💬', label: a.phase };
                const when = a.updated_at ? new Date(a.updated_at).toLocaleString() : '';
                return `
                    <div style="border:1px solid #e5e7eb;border-radius:10px;margin-bottom:12px;overflow:hidden">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f9fafb;border-bottom:1px solid #f3f4f6">
                            <span style="font-size:13px;font-weight:700;color:#111827">${meta.icon} ${Security.escape(meta.label)} — ${Security.escape(a.topic_name)}</span>
                            <span style="font-size:12px;font-weight:700;color:#2563eb">${a.score}/${a.total}</span>
                        </div>
                        <div style="padding:8px 14px">
                            ${(a.answers || []).map((qa, i) => `
                                <div style="padding:8px 0;${i > 0 ? 'border-top:1px solid #f3f4f6' : ''}">
                                    <div style="font-size:12.5px;font-weight:600;color:#374151;margin-bottom:4px">${i + 1}. ${Security.escape(qa.question)}</div>
                                    <div style="font-size:12px;color:${qa.isCorrect ? '#059669' : '#dc2626'}">
                                        ${qa.isCorrect ? '✓' : '✗'} Answered: <strong>${Security.escape(String(qa.selected ?? '—'))}</strong>
                                        ${!qa.isCorrect && qa.correct ? ` — Correct: <strong>${Security.escape(String(qa.correct))}</strong>` : ''}
                                    </div>
                                </div>`).join('')}
                        </div>
                        ${when ? `<div style="padding:6px 14px;background:#fafbfc;font-size:10.5px;color:#9ca3af;border-top:1px solid #f3f4f6">${Security.escape(when)}</div>` : ''}
                    </div>`;
            }).join('')}
        </div>`;

    Swal.update({ html });
}

function openFeedback(id) {
    const s = students.find(x => x.id === id);
    if (!s) return;
    feedbackTargetId = id;
    document.getElementById('fb-student-name').textContent = s.name;
    document.getElementById('fb-message').value = '';
    document.getElementById('fb-type').value = 'encouragement';
    renderFeedbackHistory(id);
    openModal('modal-feedback');
}

async function saveFeedback() {
    const message = Security.sanitize(document.getElementById('fb-message').value);
    const type    = document.getElementById('fb-type').value;
    const s       = students.find(x => x.id === feedbackTargetId);
    if (!s) return;

    if (!message || message.length < 5)
        return warn('Message required', 'Please write a feedback message (at least 5 characters).');

    const saveBtn = document.querySelector('#modal-feedback .btn-save');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Sending…'; }

    try {
        const data = await apiFetch('/teacher/feedback', {
            method: 'POST',
            body: JSON.stringify({ student_id: feedbackTargetId, type, message }),
        });
        feedbacks.unshift(data.feedback);
        logActivity('Feedback Sent', `Feedback sent to ${s.name}`, 'success');
        // Keep the modal open so the teacher can send another right away —
        // just clear the message and refresh the history above it.
        document.getElementById('fb-message').value = '';
        renderFeedbackHistory(feedbackTargetId);
        renderHome();
        toast('success', `Feedback sent to ${Security.escape(s.name)}!`);
    } catch (err) {
        warn('Send Failed', err.message || 'Could not send feedback. Please try again.');
    } finally {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Send Feedback'; }
    }
}

/* ============================================================
   PROGRESS
   ============================================================ */
function renderProgress() {
    const progEl = document.getElementById('progress-list');

    if (!students.length) {
        progEl.innerHTML = `<div class="empty-state"><div class="empty-icon">📈</div><h4>No student data yet</h4><p>Progress will appear here as students complete activities.</p></div>`;
        return;
    }

    progEl.innerHTML = students.map(s => `
        <div class="progress-row">
            <div class="progress-label">
                <span style="display:flex;align-items:center;gap:8px">
                    <div class="student-avatar" style="width:24px;height:24px;font-size:9px;flex-shrink:0">${Security.escape(initials(s.name))}</div>
                    ${Security.escape(s.name)}
                </span>
                <span style="font-weight:800;color:${progressColor(s.progress)}">${Security.escape(String(s.progress))}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width:${Security.escape(String(s.progress))}%;background:${progressColor(s.progress)}"></div>
            </div>
        </div>`).join('');

    const chartEl = document.getElementById('progress-chart');
    const groups  = [
        { label: 'Excellent', count: students.filter(s => s.status === 'Excellent').length,  color: '#2563eb' },
        { label: 'Good',      count: students.filter(s => s.status === 'Good').length,       color: '#10b981' },
        { label: 'Average',   count: students.filter(s => s.status === 'Average').length,    color: '#f97316' },
        { label: 'Help',      count: students.filter(s => s.status === 'Needs Help').length, color: '#ef4444' },
    ];
    const maxV = Math.max(...groups.map(g => g.count), 1);
    chartEl.innerHTML = `<div class="bar-chart">` + groups.map(g => `
        <div class="bar-group">
            <div class="bar-val">${g.count}</div>
            <div class="bar" style="height:${Math.round((g.count / maxV) * 120)}px;background:${g.color}"></div>
            <div class="bar-label">${g.label}</div>
        </div>`).join('') + `</div>`;

    const subjectEl = document.getElementById('subject-progress');
    if (!subjectCompletion.length || !subjectCompletion.some(s => s.pct > 0)) {
        subjectEl.innerHTML = '<div class="empty-state"><div class="empty-icon">📈</div><h4>No progress data yet</h4><p>Data appears as students complete modules.</p></div>';
    } else {
        subjectEl.innerHTML = subjectCompletion.map(s => `
            <div class="progress-row">
                <div class="progress-label">
                    <span>${Security.escape(s.label)}</span>
                    <span style="font-weight:800;color:${progressColor(s.pct)}">${s.pct}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:${s.pct}%;background:${progressColor(s.pct)}"></div>
                </div>
            </div>`).join('');
    }
}

/* ============================================================
   REPORTS
   ============================================================ */
function renderReports() {
    setText('r-total',    students.length);
    setText('r-avg',      avgProgress() + '%');
    setText('r-feedback', feedbacks.length);

    const withPre  = students.filter(s => s.avgPre  !== null && s.avgPre  !== undefined);
    const withPost = students.filter(s => s.avgPost !== null && s.avgPost !== undefined);
    const avgPre   = withPre.length  ? Math.round(withPre.reduce((sum, s) => sum + s.avgPre, 0) / withPre.length) : null;
    const avgPost  = withPost.length ? Math.round(withPost.reduce((sum, s) => sum + s.avgPost, 0) / withPost.length) : null;

    setText('r-avg-pre',  avgPre  === null ? '—' : avgPre + '%');
    setText('r-avg-post', avgPost === null ? '—' : avgPost + '%');
    setText('r-improvement', (avgPre === null || avgPost === null) ? '—' : `${avgPost - avgPre >= 0 ? '+' : ''}${avgPost - avgPre}%`);

    // Display sections in the sections-container for management
    renderSectionsContainer();
}

/** Sections to report on, and the real students within each. */
function getReportSections() {
    return allSections.length ? allSections : sections;
}
function sectionAvgProgress(sectionStudents) {
    return sectionStudents.length
        ? Math.round(sectionStudents.reduce((sum, s) => sum + s.progress, 0) / sectionStudents.length)
        : 0;
}
const pctOrDash = v => (v === null || v === undefined ? '—' : `${v}%`);

/** Single "Export" button entry point — lets the teacher pick PDF or Excel. */
function showReportExportPicker() {
    const reportSections = getReportSections();
    if (!reportSections.length) return Swal.fire({ icon: 'warning', title: 'No Sections', text: 'Create at least one section first.', confirmButtonColor: '#2563eb' });

    // Kick off the export library download now so it's ready (or nearly so) by the
    // time the teacher actually picks a format below.
    loadExportLibs().catch(() => {});

    Swal.fire({
        title: 'Export Report',
        text: 'Choose a format to download.',
        icon: 'question',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'PDF',
        denyButtonText: 'Excel',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb',
        denyButtonColor: '#16a34a',
    }).then(async r => {
        if (r.isConfirmed) {
            try {
                await buildAndDownloadPdfReport(reportSections);
                logActivity('Report Generated', 'PDF report was downloaded', 'report');
                toast('success', 'PDF report downloaded!');
            } catch (err) {
                console.error('PDF generation error:', err);
                warn('PDF Generation Failed', 'Could not generate the PDF. Please try again.');
            }
        } else if (r.isDenied) {
            try {
                await buildAndDownloadExcelReport(reportSections);
                logActivity('Report Generated', 'Excel report was downloaded', 'report');
                toast('success', 'Excel report downloaded!');
            } catch (err) {
                console.error('Excel generation error:', err);
                warn('Excel Generation Failed', 'Could not generate the spreadsheet. Please try again.');
            }
        }
    });
}

/** Loads jsPDF, jspdf-autotable, and SheetJS from CDN on first use instead of
 *  eagerly on every page load. autoTable must load after jsPDF since it
 *  attaches to the jsPDF prototype, so the scripts load sequentially. */
let exportLibsPromise = null;
function loadExportLibs() {
    if (exportLibsPromise) return exportLibsPromise;
    const sources = [
        'https://cdnjs.cloudflare.com/ajax/libs/jspdf/4.2.1/jspdf.umd.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js',
    ];
    exportLibsPromise = sources.reduce((chain, src) => chain.then(() => new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = () => reject(new Error(`Failed to load ${src}`));
        document.head.appendChild(script);
    })), Promise.resolve());
    return exportLibsPromise;
}

/** Build a real PDF from the teacher's real sections + students and trigger a download. */
async function buildAndDownloadPdfReport(reportSections) {
    await loadExportLibs();
    if (!window.jspdf || !window.jspdf.jsPDF) {
        throw new Error('PDF library failed to load. Check your connection and try again.');
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const teacherName = window.__USER__?.name || 'Teacher';

    doc.setFontSize(18);
    doc.text('Student Performance Report', 14, 18);
    doc.setFontSize(10);
    doc.setTextColor(110);
    doc.text(`Generated by ${teacherName} on ${new Date().toLocaleString()}`, 14, 25);

    let y = 34;
    reportSections.forEach(sec => {
        const sectionStudents = students.filter(s => s.section_id === sec.id);
        const avg = sectionAvgProgress(sectionStudents);

        if (y > 265) { doc.addPage(); y = 20; }

        doc.setFontSize(13);
        doc.setTextColor(20);
        doc.text(`${sec.name}  —  ${sectionStudents.length} student(s), ${avg}% avg progress`, 14, y);
        y += 4;

        doc.autoTable({
            startY: y,
            head: sectionStudents.length ? [['#', 'Name', 'Student ID', 'Status', 'Progress', 'Avg Pre-Test', 'Avg Post-Test']] : undefined,
            body: sectionStudents.length
                ? sectionStudents.map((s, i) => [i + 1, s.name, s.studentId || '—', s.status, `${s.progress}%`, pctOrDash(s.avgPre), pctOrDash(s.avgPost)])
                : [['No students enrolled in this section yet.']],
            headStyles: { fillColor: [37, 99, 235] },
            styles: { fontSize: 9 },
            margin: { left: 14, right: 14 },
        });

        y = doc.lastAutoTable.finalY + 12;
    });

    doc.save(`student-report-${new Date().toISOString().slice(0, 10)}.pdf`);
}

/** Build a real .xlsx workbook from the teacher's real sections + students and trigger a download. */
async function buildAndDownloadExcelReport(reportSections) {
    await loadExportLibs();
    if (!window.XLSX) {
        throw new Error('Spreadsheet library failed to load. Check your connection and try again.');
    }

    const wb = XLSX.utils.book_new();
    const usedSheetNames = new Set();

    // Summary sheet first
    const summaryRows = [['Section', 'Students', 'Avg Progress']];
    reportSections.forEach(sec => {
        const sectionStudents = students.filter(s => s.section_id === sec.id);
        summaryRows.push([sec.name, sectionStudents.length, `${sectionAvgProgress(sectionStudents)}%`]);
    });
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(summaryRows), 'Summary');

    reportSections.forEach(sec => {
        const sectionStudents = students.filter(s => s.section_id === sec.id);
        const rows = [['#', 'Name', 'Student ID', 'Status', 'Progress', 'Avg Pre-Test', 'Avg Post-Test']];
        sectionStudents.forEach((s, i) => {
            rows.push([i + 1, s.name, s.studentId || '—', s.status, `${s.progress}%`, pctOrDash(s.avgPre), pctOrDash(s.avgPost)]);
        });
        if (!sectionStudents.length) rows.push(['No students enrolled in this section yet.']);

        // Excel sheet names: max 31 chars, no \ / ? * [ ] :, and must be unique.
        let sheetName = sec.name.replace(/[\\/?*[\]:]/g, ' ').trim().slice(0, 31) || 'Section';
        let suffix = 2;
        while (usedSheetNames.has(sheetName)) {
            sheetName = `${sheetName.slice(0, 28)} (${suffix++})`;
        }
        usedSheetNames.add(sheetName);

        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(rows), sheetName);
    });

    XLSX.writeFile(wb, `student-report-${new Date().toISOString().slice(0, 10)}.xlsx`);
}

/* ============================================================
   MODULES — fetched from Supabase + merged with module_status
   ============================================================ */
function getFilteredModules() {
    const q     = Security.sanitize(document.getElementById('module-search')?.value || '').toLowerCase();
    const topic = document.getElementById('module-topic-filter')?.value || '';
    return modulesData.filter(m =>
        m.title.toLowerCase().includes(q) &&
        (!topic || m.topic === topic)
    );
}

/* ---------------------------------------------------------------
   Status badge helper — maps displayStatus → CSS class
   Published       → green  (badge-good)
   Pending Review  → orange (badge-average)
   Rejected        → red    (badge-danger)
--------------------------------------------------------------- */
function moduleBadgeClass(status) {
    if (status === 'Published')      return 'badge-good';
    if (status === 'Rejected')       return 'badge-danger';
    return 'badge-average';   // Pending Review
}

/* ---------------------------------------------------------------
   loadAndRenderModules — re-fetches from Supabase every time
   the teacher opens the Modules page.
--------------------------------------------------------------- */
async function loadAndRenderModules() {
    const grid = document.getElementById('modules-grid');
    if (grid) {
        grid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">⏳</div>
                <h4>Loading modules…</h4>
                <p>Fetching latest status from Supabase.</p>
            </div>`;
    }

    setText('mod-total',      '…');
    setText('mod-published',  '…');
    setText('mod-draft',      '…');
    setText('mod-completion', '…');

    try {
        modulesData = await fetchModulesFromSupabase();
        lastModulesSnapshot = JSON.stringify(modulesData);
        renderModules();
    } catch (err) {
        console.error('Supabase load error:', err);
        modulesData = [];
        if (grid) {
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h4>Could not load modules</h4>
                    <p>${Security.escape(err.message)}</p>
                    <button class="primary-btn" style="margin-top:12px"
                            onclick="loadAndRenderModules()">Retry</button>
                </div>`;
        }
        setText('mod-total', '0'); setText('mod-published', '0');
        setText('mod-draft', '0'); setText('mod-completion', '0%');
    }
}

/**
 * Modules tab reads directly from Supabase (storage bucket + module_status
 * table), with no Laravel route in the loop — so there's no server-side
 * ETag/304 to lean on here like the other auto-refresh pages. Instead:
 * fetch on the same interval, but skip the re-render entirely unless the
 * fetched data actually differs from what's already shown. This is how a
 * teacher sees an admin's approve/reject decision land without reloading.
 */
let lastModulesSnapshot = null;

async function pollModulesTick() {
    const fresh = await fetchModulesFromSupabase();
    const snapshot = JSON.stringify(fresh);
    if (snapshot === lastModulesSnapshot) return;
    lastModulesSnapshot = snapshot;
    modulesData = fresh;
    renderModules();
}

/* ============================================================
   LOAD SECTIONS FROM DATABASE
   ============================================================ */
/* ============================================================
   SECTIONS (Reports page) – Teacher Dashboard
   ============================================================ */

function loadSectionsForReports() {
    // Scoped to this teacher's own sections (/api/sections is the public,
    // unscoped list used by the registration form — using it here leaked
    // every other teacher's section names into this teacher's Reports tab).
    apiFetch('/teacher/sections/list')
        .then(data => {
            allSections = data.sections || [];
            renderSectionsContainer();
        })
        .catch(err => {
            console.error('Error loading sections:', err);
            allSections = [];
            renderSectionsContainer();
        });
}

function renderSectionsContainer() {
    const container = document.getElementById('sections-container');
    if (!allSections || allSections.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">🏫</div>
                <h4>No sections yet</h4>
                <p>Click "Add Section" to create your first section.</p>
            </div>`;
        return;
    }

    const colors = ['#3b82f6', '#10b981', '#f97316', '#8b5cf6', '#ec4899', '#06b6d4'];

    container.innerHTML = allSections.map((sec, idx) => {
        const colorHex = colors[idx % colors.length];

        // Real students in this section, from the already-loaded roster
        // (StudentController::getTeacherStudents), not a placeholder count.
        const sectionStudents = students.filter(s => s.section_id === sec.id);
        const studentCount = sectionStudents.length;
        const avgProgress = studentCount
            ? Math.round(sectionStudents.reduce((sum, s) => sum + s.progress, 0) / studentCount)
            : 0;
        const needsAttention = sectionStudents.filter(s => s.status === 'Needs Help').length;
        const top = [...sectionStudents].sort((a, b) => b.progress - a.progress)[0];

        return `
            <div style="border:1px solid #e5e7eb;border-radius:16px;margin-bottom:20px;overflow:hidden;background:white;box-shadow:0 1px 3px rgba(0,0,0,0.05)">
                <!-- Section Header -->
                <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:24px;border-bottom:1px solid #f3f4f6">
                    <div style="display:flex;align-items:center;gap:16px;flex:1">
                        <!-- Colored Badge -->
                        <div style="width:56px;height:56px;background:${colorHex};color:white;border-radius:14px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:24px;flex-shrink:0;box-shadow:0 2px 8px ${colorHex}33">
                            ${idx + 1}
                        </div>
                        <!-- Section Info -->
                        <div>
                            <div style="font-weight:700;color:#111827;font-size:16px">${Security.escape(sec.name)}</div>
                            <div style="font-size:13px;color:#6b7280;display:flex;gap:16px;margin-top:6px">
                                <span>👥 <strong style="color:#4b5563">${studentCount}</strong> students</span>
                                <span>📊 Avg: <strong style="color:#4b5563">${avgProgress}%</strong></span>
                            </div>
                        </div>
                    </div>
                    <!-- Action Icons -->
                    <div style="display:flex;gap:6px">
                        <button onclick="editSection(${sec.id}, '${Security.escape(sec.name)}')" 
                                style="background:none;border:none;cursor:pointer;padding:10px;color:#9ca3af;transition:color 0.2s;border-radius:8px;hover:{background:#f3f4f6;color:#6b7280}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button onclick="deleteSection(${sec.id})"
                                style="background:none;border:none;cursor:pointer;padding:10px;color:#9ca3af;transition:color 0.2s;border-radius:8px;hover:{background:#fef2f2;color:#ef4444}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Section Content: real student roster -->
                <div style="padding:16px 24px;background:#fafbfc">
                    ${studentCount === 0
                        ? `<div style="text-align:center;padding:32px 0">
                               <div style="color:#d1d5db;font-size:64px;margin-bottom:12px;opacity:0.8">👥</div>
                               <div style="color:#9ca3af;font-size:15px;font-weight:500">No students in this section yet</div>
                           </div>`
                        : sectionStudents.map(s => `
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f3f4f6">
                                <div style="display:flex;align-items:center;gap:10px;min-width:0">
                                    <div class="student-avatar" style="width:28px;height:28px;font-size:10px;flex-shrink:0">${Security.escape(initials(s.name))}</div>
                                    <span style="font-size:13.5px;color:#111827;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${Security.escape(s.name)}</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
                                    <span class="status-badge ${badgeClass(s.status)}">${Security.escape(s.status)}</span>
                                    <span style="font-size:12.5px;font-weight:700;color:${progressColor(s.progress)};width:36px;text-align:right">${s.progress}%</span>
                                    <button onclick="viewStudentAnswers(${s.id}, '${Security.escape(s.name)}')" title="View this student's quiz answers"
                                            style="background:none;border:none;cursor:pointer;padding:6px;color:#9ca3af;border-radius:6px;display:flex">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px">
                                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>`).join('')
                    }
                </div>

                <!-- Section Footer -->
                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 24px;background:#f9fafb;border-top:1px solid #f3f4f6;font-size:13px;color:#6b7280">
                    <span>Top: <strong style="color:#111827">${top ? Security.escape(top.name) : 'N/A'}</strong></span>
                    <span>Needs attention: <strong style="color:#111827">${needsAttention} students</strong></span>
                </div>
            </div>
        `;
    }).join('');
}

async function loadSections() {
    try {
        // Scoped to this teacher's own sections — /api/sections is the
        // public, unscoped list used by the registration form.
        const data = await apiFetch('/teacher/sections/list');
        sections = data.sections || [];
    } catch (err) {
        console.error('Error loading sections:', err);
        sections = [];
    }
}

/* ============================================================
   SECTION MANAGEMENT FUNCTIONS
   ============================================================ */
function openAddSection() {
    Swal.fire({
        title: 'Add New Section',
        html: `
            <div style="text-align:left">
                <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:6px">
                    Section name
                </label>
                <input type="text" id="swal-section-name"
                       placeholder="e.g. Section A, Grade 10 – Einstein"
                       style="width:100%;padding:11px 13px;background:#F1F5F9;
                              border:1.5px solid transparent;border-radius:11px;
                              font-size:14px;color:#333;outline:none;
                              transition:.2s;box-sizing:border-box"
                       onfocus="this.style.background='#fff';this.style.borderColor='#1E88E5'"
                       onblur="this.style.background='#F1F5F9';this.style.borderColor='transparent'">
                <p style="font-size:12px;color:#9ca3af;margin:8px 0 0">
                    Students will see this name in the signup dropdown.
                </p>
            </div>`,
        confirmButtonText: 'Add Section',
        confirmButtonColor: '#1E88E5',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        preConfirm: () => {
            const name = document.getElementById('swal-section-name').value.trim();
            if (!name) {
                Swal.showValidationMessage('Please enter a section name');
                return false;
            }
            return name;
        }
    }).then(result => {
        if (result.isConfirmed) {
            saveSectionToServer(result.value);
        }
    });
}

async function saveSectionToServer(name) {
    try {
        const res = await fetch('/teacher/sections', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ name }),
        });
        if (!res.ok) {
            const err = await res.json();
            throw new Error(err.message || 'Failed to create section');
        }
        Swal.fire({ icon: 'success', title: 'Section added!', text: `"${name}" is now visible in student signup.`, timer: 2000, showConfirmButton: false });
        await loadSectionsForReports();
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    }
}

function editSection(id, currentName) {
    Swal.fire({
        title: 'Edit Section',
        input: 'text',
        inputValue: currentName,
        inputAttributes: { autocomplete: 'off' },
        confirmButtonText: 'Save',
        confirmButtonColor: '#1E88E5',
        showCancelButton: true,
        inputValidator: v => !v.trim() && 'Section name cannot be empty',
    }).then(async result => {
        if (!result.isConfirmed) return;
        try {
            const res = await fetch(`/teacher/sections/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ name: result.value.trim() }),
            });
            if (!res.ok) throw new Error('Failed to update section');
            await loadSectionsForReports();
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    });
}

function deleteSection(id) {
    Swal.fire({
        title: 'Delete section?',
        text: 'Students in this section will have their section unassigned.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#ef4444',
    }).then(async result => {
        if (!result.isConfirmed) return;
        try {
            const res = await fetch(`/teacher/sections/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            if (!res.ok) throw new Error('Failed to delete section');
            await loadSectionsForReports();
            Swal.fire('Deleted!', '', 'success');
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    });
}

function renderModules() {
    const published = modulesData.filter(m => m.status === 'Published').length;
    const pending   = modulesData.filter(m => m.status === 'Pending Review').length;
    const rejected  = modulesData.filter(m => m.status === 'Rejected').length;
    const avgComp   = modulesData.length
        ? Math.round(modulesData.reduce((s, m) => s + (m.completion || 0), 0) / modulesData.length)
        : 0;

    setText('mod-total',      modulesData.length);
    setText('mod-published',  published);
    setText('mod-draft',      pending + rejected);
    setText('mod-completion', avgComp + '%');

    const grid     = document.getElementById('modules-grid');
    const filtered = getFilteredModules();

    if (!filtered.length) {
        grid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h4>${modulesData.length ? 'No modules match your search' : 'No modules uploaded yet'}</h4>
                <p>${modulesData.length ? 'Try a different search or topic filter.' : 'Click "+ Add Module" to upload your first file.'}</p>
            </div>`;
        return;
    }

    grid.innerHTML = filtered.map(m => {
        const fileBadge = m.fileName
            ? `<span class="module-file-badge">
                 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                      style="width:13px;height:13px;flex-shrink:0">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                      <polyline points="14 2 14 8 20 8"/>
                 </svg>
                 ${m.fileUrl
                     ? `<a href="${Security.escape(m.fileUrl)}" target="_blank" rel="noopener noreferrer"
                           style="color:inherit;text-decoration:underline">${Security.escape(m.fileName)}</a>`
                     : Security.escape(m.fileName)
                 }
               </span>`
            : '';

        const rejectedHint = m.status === 'Rejected'
            ? `<div style="margin-top:6px;padding:6px 10px;background:#fef2f2;border-radius:6px;
                           font-size:11px;color:#dc2626;font-weight:600">
                   ⚠️ Rejected by admin — please review and re-upload if needed.
               </div>`
            : '';

        const pendingHint = m.status === 'Pending Review'
            ? `<div style="margin-top:6px;padding:6px 10px;background:#fff7ed;border-radius:6px;
                           font-size:11px;color:#ea580c;font-weight:600">
                   🕐 Awaiting admin approval before students can access this module.
               </div>`
            : '';

        return `
        <div class="module-card">
            <div class="module-card-header">
                <div class="module-icon-wrap ${topicTheme(m.topic)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <span class="status-badge ${moduleBadgeClass(m.status)}">${Security.escape(m.status)}</span>
            </div>
            <div class="module-card-title">${Security.escape(m.title)}</div>
            <div class="module-card-topic">${Security.escape(m.topic)}</div>
            <div class="module-card-desc">${Security.escape(m.desc || '')}</div>
            ${fileBadge}
            ${rejectedHint}
            ${pendingHint}
            <div class="module-card-footer">
                <div>
                    <div class="progress-bar" style="height:5px;margin-bottom:4px">
                        <div class="progress-fill" style="width:${m.completion || 0}%;background:${progressColor(m.completion || 0)}"></div>
                    </div>
                    <span style="font-size:11px;color:var(--text-4);font-weight:600">${m.completion || 0}% avg. completion</span>
                </div>
                <div class="module-card-actions">
                    <button class="tbl-btn view" onclick="viewModule('${Security.escape(String(m.id))}')">View</button>
                    ${m.status === 'Published'
                        ? `<button class="tbl-btn" onclick="sendToDownloads('${Security.escape(String(m.id))}')"
                               style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;font-weight:700">
                               📥 Send
                           </button>`
                        : `<button class="tbl-btn edit" onclick="editModule('${Security.escape(String(m.id))}')">Edit</button>`
                    }
                    <button class="tbl-btn feedback" onclick="deleteModule('${Security.escape(String(m.id))}')">Delete</button>
                </div>
            </div>
        </div>`;
    }).join('');
}

function filterModules() { renderModules(); }

function cancelModule() {
    // Reset fields
    document.getElementById('mod-title').value   = '';
    document.getElementById('mod-desc').value    = '';
    document.getElementById('mod-topic').value   = 'Module 1: Sequences and Series';
    document.getElementById('mod-status').value  = 'Draft';
    document.getElementById('mod-edit-id').value = '';
    document.getElementById('mod-modal-title').textContent = 'Add Module';

    // Clear file
    pendingFile = null;
    const fileInput   = document.getElementById('mod-file');
    const filePreview = document.getElementById('mod-file-preview');
    if (fileInput)   fileInput.value = '';
    if (fileInput)   fileInput.removeAttribute('data-existing-file');
    if (filePreview) filePreview.classList.remove('visible');

    // Force close modal with multiple fallback methods
    const modal = document.getElementById('modal-add-module');
    if (modal) {
        modal.classList.remove('open');          // Remove 'open' class (primary method)
        modal.classList.remove('active');        // Remove 'active' class (fallback)
    }
    document.body.style.overflow = '';           // Restore scroll
}

function resetModuleForm(clearFile = false) {
    document.getElementById('mod-title').value  = '';
    document.getElementById('mod-desc').value   = '';
    document.getElementById('mod-topic').value  = 'Module 1: Sequences and Series';
    document.getElementById('mod-status').value = 'Draft';
    document.getElementById('mod-edit-id').value = '';
    document.getElementById('mod-modal-title').textContent = 'Add Module';
    
    // Only clear file if explicitly requested (e.g., on Cancel button)
    if (clearFile) {
        pendingFile = null;
        const fileInput   = document.getElementById('mod-file');
        const filePreview = document.getElementById('mod-file-preview');
        const uploadArea  = document.getElementById('mod-file-area');
        
        if (fileInput)   fileInput.value = '';
        if (fileInput)   fileInput.removeAttribute('data-existing-file');
        if (filePreview) filePreview.classList.remove('visible');
        if (uploadArea) {
            uploadArea.style.display = '';
            uploadArea.style.removeProperty('display');
        }
    }
}

function openAddModule() {
    // Always reset to "Add" mode - clear form fields
    document.getElementById('mod-title').value  = '';
    document.getElementById('mod-desc').value   = '';
    document.getElementById('mod-topic').value  = 'Module 1: Sequences and Series';
    document.getElementById('mod-status').value = 'Draft';
    document.getElementById('mod-edit-id').value = '';
    document.getElementById('mod-modal-title').textContent = 'Add Module';
    
    // Reset file upload section completely
    pendingFile = null;
    const fileInput   = document.getElementById('mod-file');
    const filePreview = document.getElementById('mod-file-preview');
    const uploadArea  = document.getElementById('mod-file-area');
    
    if (fileInput) fileInput.value = '';
    if (filePreview) filePreview.classList.remove('visible');
    if (uploadArea) {
        uploadArea.style.display = '';
        uploadArea.style.removeProperty('display');  // Ensure no inline display:none
    }
    
    openModal('modal-add-module');
}

async function saveModule() {
    const title   = Security.sanitize(document.getElementById('mod-title').value.trim());
    const desc    = Security.sanitize(document.getElementById('mod-desc').value.trim());
    const topic   = document.getElementById('mod-topic').value.trim();
    const editId  = document.getElementById('mod-edit-id').value.trim(); // ← CRITICAL: check this

    if (!title || title.length < 3)
        return warn('Title required', 'Please enter a module title (at least 3 characters).');

    const saveBtn = document.querySelector('#modal-add-module .btn-save');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }

    try {
        let fileName    = null;
        let fileSize    = null;
        let fileUrl     = null;
        let storageName = null;

        // Upload NEW file if selected
        if (pendingFile) {
            const MAX_BYTES = 20 * 1024 * 1024;
            if (pendingFile.size > MAX_BYTES) {
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Module'; }
                return warn('File too large', 'Please select a file smaller than 20 MB.');
            }
            const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg'];
            const ext = pendingFile.name.split('.').pop()?.toLowerCase();
            // The <input accept> attribute is advisory only — drag-and-drop
            // bypasses it entirely, so re-check the extension here too.
            if (!ext || !ALLOWED_EXTENSIONS.includes(ext)) {
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Module'; }
                return warn('Unsupported file type', `Allowed types: ${ALLOWED_EXTENSIONS.join(', ')}`);
            }
            if (saveBtn) saveBtn.textContent = 'Uploading…';
            
            fileName = pendingFile.name;
            fileSize = pendingFile.size;
            const result = await uploadFileToSupabase(pendingFile);
            fileUrl     = result.publicUrl;
            storageName = result.path;
        }

        // ─────────────────────────────────────────────────────────────
        // EDIT MODE: update existing module
        // ─────────────────────────────────────────────────────────────
        if (editId) {
            const moduleIndex = modulesData.findIndex(m => String(m.id) === String(editId));
            if (moduleIndex === -1) {
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Module'; }
                return warn('Error', 'Module not found in local data.');
            }

            const existing = modulesData[moduleIndex];

            // Update array with new data, but preserve id/dbId/status/completion
            modulesData[moduleIndex] = {
                ...existing,
                title,
                desc,
                topic,
                // Keep old file if no new file uploaded, otherwise use new file
                fileName:    pendingFile ? fileName    : existing.fileName,
                fileSize:    pendingFile ? fileSize    : existing.fileSize,
                fileUrl:     pendingFile ? fileUrl     : existing.fileUrl,
                storageName: pendingFile ? storageName : existing.storageName,
            };

            // Update Supabase DB if has dbId
            console.log('dbId:', existing.dbId);
            console.log('Saving:', { module_title: title, module_desc: desc, module_topic: topic });

            if (existing.dbId) {
                try {
                    if (saveBtn) saveBtn.textContent = 'Updating database…';
                    const updateResult = await sbUpdate(STATUS_TABLE, existing.dbId, {
                        module_title: title,
                        module_desc: desc,
                        module_topic: topic,
                        ...(pendingFile && {
                            file_name: storageName,
                            file_url: fileUrl,
                        }),
                    });
                    console.log('✅ DB updated:', updateResult);
                } catch (err) {
                    console.error('❌ DB update failed:', err.message);
                    // Show error to teacher so they know it failed
                    warn('Save Failed', `Could not save to database: ${err.message}`);
                    if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Module'; }
                    return; // ← stop here, don't close modal
                }
            } else {
                // dbId is missing — upsert as new row instead
                console.warn('No dbId found, upserting instead…');
                try {
                    const inserted = await sbUpsert(STATUS_TABLE, [{
                        file_name: existing.storageName || null,
                        file_url: existing.fileUrl || null,
                        status: 'pending',
                        module_title: title,
                        module_desc: desc,
                        module_topic: topic,
                    }]);
                    if (Array.isArray(inserted) && inserted[0]) {
                        modulesData[moduleIndex].dbId = inserted[0].id;
                        console.log('✅ Upserted with new dbId:', inserted[0].id);
                    }
                } catch (err) {
                    console.error('Upsert fallback failed:', err.message);
                }
            }

            logActivity('Module Updated', `"${title}" was edited`, 'module');
            pendingFile = null;
            closeModal('modal-add-module');
            renderModules();
            toast('success', `"${Security.escape(title)}" updated successfully!`);

        // ─────────────────────────────────────────────────────────────
        // CREATE MODE: make new module
        // ─────────────────────────────────────────────────────────────
        } else {
            // Create status row in Supabase (new file or just metadata)
            if (saveBtn) saveBtn.textContent = 'Saving to database…';
            
            let dbId = null;
            try {
                const statusData = {
                    file_name: storageName || null,
                    file_url: fileUrl || null,
                    status: 'pending',
                    module_title: title,
                    module_desc: desc,
                    module_topic: topic,
                };
                const inserted = await sbUpsert(STATUS_TABLE, [statusData]);
                if (Array.isArray(inserted) && inserted[0]) {
                    dbId = inserted[0].id;
                }
            } catch (err) {
                console.warn('Could not create database row:', err.message);
            }

            // Add to local array
            const newModule = {
                id:          Date.now(),
                storageName: storageName || null,
                title,
                desc,
                topic,
                status:      'Pending Review',
                dbStatus:    'pending',
                completion:  0,
                date:        dateNow(),
                fileName:    fileName || null,
                fileSize:    fileSize || 0,
                fileUrl:     fileUrl || null,
                dbId:        dbId,
            };
            modulesData.push(newModule);

            logActivity('Module Uploaded', `"${title}" submitted for admin review`, 'module');
            pendingFile = null;
            closeModal('modal-add-module');
            renderModules();
            toast('success', `"${Security.escape(title)}" uploaded! Waiting for admin approval.`);
        }

    } catch (err) {
        console.error('saveModule error:', err);
        warn('Upload Failed', err.message || 'Could not save the module. Please try again.');
    } finally {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Module'; }
    }
}

function viewModule(id) {
    const m = modulesData.find(x => String(x.id) === String(id));
    if (!m) return;
    const fileHtml = m.fileName
        ? `<div style="display:flex;justify-content:space-between;margin-top:8px">
               <span style="font-size:12px;color:#6b7280;font-weight:600">Attached File</span>
               <span style="font-size:12px;font-weight:700;color:#2563eb">
                   ${m.fileUrl
                       ? `<a href="${Security.escape(m.fileUrl)}" target="_blank" rel="noopener noreferrer">${Security.escape(m.fileName)}</a>`
                       : Security.escape(m.fileName)
                   }
               </span>
           </div>`
        : '';
    Swal.fire({
        title: Security.escape(m.title),
        html: `
            <div style="text-align:left;font-family:'Plus Jakarta Sans',sans-serif">
                <div style="background:#f4f6fb;border-radius:10px;padding:14px;margin-bottom:12px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                        <span style="font-size:12px;color:#6b7280;font-weight:600">Topic</span>
                        <span style="font-size:13px;font-weight:700;color:#111827">${Security.escape(m.topic)}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                        <span style="font-size:12px;color:#6b7280;font-weight:600">Admin Status</span>
                        <span style="font-size:12px;font-weight:700">${Security.escape(m.status)}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:12px;color:#6b7280;font-weight:600">Avg. Completion</span>
                        <span style="font-size:12px;color:#9ca3af">${m.completion || 0}%</span>
                    </div>
                    ${fileHtml}
                </div>
                <p style="font-size:13px;color:#6b7280">${Security.escape(m.desc || 'No description provided.')}</p>
            </div>`,
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'Close',
    });
}

function editModule(id) {
    const m = modulesData.find(x => String(x.id) === String(id));
    if (!m) return;
    
    // Populate form with module data
    document.getElementById('mod-title').value  = m.title || '';
    document.getElementById('mod-desc').value   = m.desc || '';
    document.getElementById('mod-topic').value  = m.topic || 'Module 1: Sequences and Series';
    document.getElementById('mod-status').value = 'Draft';
    document.getElementById('mod-edit-id').value = String(m.id);
    document.getElementById('mod-modal-title').textContent = 'Edit Module';
    
    // Clear pending file to allow fresh upload if needed
    pendingFile = null;
    const fileInput = document.getElementById('mod-file');
    const uploadArea = document.getElementById('mod-file-area');
    if (fileInput) fileInput.value = '';
    
    // Restore the existing file in the preview
    if (m.fileName) {
        const filePreview = document.getElementById('mod-file-preview');
        const filePreviewName = document.getElementById('mod-file-name');
        const filePreviewSize = document.getElementById('mod-file-size');
        if (filePreview) filePreview.classList.add('visible');
        if (uploadArea) uploadArea.style.display = 'none';  // Hide upload area when showing existing file
        if (filePreviewName) filePreviewName.textContent = m.fileName;
        if (filePreviewSize) filePreviewSize.textContent = formatFileSize(m.fileSize);
        // Store the existing file info so we can keep it if no new file is selected
        if (fileInput) fileInput.setAttribute('data-existing-file', m.storageName);
    } else {
        // Clear file if no existing file
        const filePreview = document.getElementById('mod-file-preview');
        if (filePreview) filePreview.classList.remove('visible');
        if (uploadArea) uploadArea.style.display = '';  // Show upload area when there's no existing file
        if (fileInput) fileInput.removeAttribute('data-existing-file');
    }
    
    // Open the modal
    openModal('modal-add-module');
}

async function deleteModule(id) {
    const m = modulesData.find(x => String(x.id) === String(id));
    if (!m) return;
    Swal.fire({
        title: 'Delete Module?',
        text: `"${m.title}" will be permanently deleted.`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Delete', cancelButtonText: 'Cancel',
    }).then(async r => {
        if (!r.isConfirmed) return;
        
        try {
            // Delete file from Supabase Storage if it exists
            if (m.storageName) {
                try {
                    await deleteFileFromSupabase(m.storageName);
                } catch (fileErr) {
                    console.warn('Could not delete file from storage:', fileErr.message);
                    // Continue with database deletion even if file deletion fails
                }
            }
            
            // Delete from database if it has a dbId
            if (m.dbId) {
                try {
                    await sbDelete(STATUS_TABLE, m.dbId);
                } catch (dbErr) {
                    console.warn('Could not delete from database:', dbErr.message);
                }
            }
            
            modulesData = modulesData.filter(x => String(x.id) !== String(id));
            logActivity('Module Deleted', `"${m.title}" permanently deleted`, 'module');
            renderModules();
            toast('success', 'Module permanently deleted.');
        } catch (err) {
            console.error('Delete error:', err);
            warn('Delete Failed', err.message || 'Could not delete the module.');
        }
    });
}

/* ============================================================
   SEND TO DOWNLOADS
   Lets the teacher assign a Published module to a module group
   so it appears on the student's Offline Materials page.
   ============================================================ */
function sendToDownloads(id) {
    const m = modulesData.find(x => String(x.id) === String(id));
    if (!m) return;
    if (m.status !== 'Published')
        return warn('Not published', 'Only published modules can be sent to student downloads.');

    // Pre-select the radio matching the module's current topic
    const currentTopic = m.topic || 'Module 1: Sequences and Series';
    const topicToRadio = {
        'Module 1: Sequences and Series': 'mod1',
        'Module 2: Polynomials':          'mod2',
        'Module 3: Advanced Equations':   'mod3',
    };
    const preselect = topicToRadio[currentTopic] || 'mod1';

    Swal.fire({
        position: 'center',
        title: 'Send to student downloads',
        html: `
            <div style="text-align:left;font-family:'Plus Jakarta Sans',sans-serif">
                <p style="font-size:12px;color:#6b7280;margin:0 0 14px">
                    Sending: <strong style="color:#111827">${Security.escape(m.title)}</strong><br>
                    <span style="font-size:11px">Default materials are not removed — this adds your file alongside them.</span>
                </p>

                <div style="display:flex;flex-direction:column;gap:8px">

                    <label for="r-mod1" style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:10px;border:2px solid #bfdbfe;background:#eff6ff;cursor:pointer" id="lbl-mod1">
                        <input type="radio" id="r-mod1" name="swal-mod" value="Module 1: Sequences and Series"
                               ${preselect === 'mod1' ? 'checked' : ''}
                               style="margin-top:2px;accent-color:#2563eb;width:15px;height:15px;flex-shrink:0">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#1e40af">Module 1</div>
                            <div style="font-size:12px;color:#2563eb;font-weight:600;margin-top:1px">Sequences and Series</div>
                        </div>
                    </label>

                    <label for="r-mod2" style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:10px;border:0.5px solid #e5e7eb;background:#fff;cursor:pointer" id="lbl-mod2">
                        <input type="radio" id="r-mod2" name="swal-mod" value="Module 2: Polynomials"
                               ${preselect === 'mod2' ? 'checked' : ''}
                               style="margin-top:2px;accent-color:#16a34a;width:15px;height:15px;flex-shrink:0">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#14532d">Module 2</div>
                            <div style="font-size:12px;color:#16a34a;font-weight:600;margin-top:1px">Polynomials and Polynomial Equations</div>    
                        </div>
                    </label>

                    <label for="r-mod3" style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:10px;border:0.5px solid #e5e7eb;background:#fff;cursor:pointer" id="lbl-mod3">
                        <input type="radio" id="r-mod3" name="swal-mod" value="Module 3: Advanced Equations"
                               ${preselect === 'mod3' ? 'checked' : ''}
                               style="margin-top:2px;accent-color:#d97706;width:15px;height:15px;flex-shrink:0">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#78350f">Module 3</div>
                            <div style="font-size:12px;color:#d97706;font-weight:600;margin-top:1px">Advanced Equations and Functions</div>
                        </div>
                    </label>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '📥 Send to downloads',
        cancelButtonText: 'Cancel',
        didOpen: () => {
            // Live highlight whichever radio is selected
            const radios = document.querySelectorAll('input[name="swal-mod"]');
            const lbls   = {
                'r-mod1': { border: '2px solid #bfdbfe', bg: '#eff6ff' },
                'r-mod2': { border: '2px solid #bbf7d0', bg: '#f0fdf4' },
                'r-mod3': { border: '2px solid #fde68a', bg: '#fffbeb' },
            };

            function refreshHighlight() {
                radios.forEach(r => {
                    const lbl = document.getElementById('lbl-' + r.id.replace('r-', ''));
                    if (!lbl) return;
                    if (r.checked) {
                        lbl.style.border      = lbls[r.id].border;
                        lbl.style.background  = lbls[r.id].bg;
                    } else {
                        lbl.style.border      = '0.5px solid #e5e7eb';
                        lbl.style.background  = '#fff';
                    }
                });
            }

            refreshHighlight(); // apply on open
            radios.forEach(r => r.addEventListener('change', refreshHighlight));
        },
    }).then(async r => {
        if (!r.isConfirmed) return;

        const chosenTopic = document.querySelector('input[name="swal-mod"]:checked')?.value;
        if (!chosenTopic) return warn('No selection', 'Please pick a module group.');

        try {
            if (m.dbId) {
                await sbUpdate(STATUS_TABLE, m.dbId, { module_topic: chosenTopic });
            }
            // Update local cache
            const idx = modulesData.findIndex(x => String(x.id) === String(id));
            if (idx !== -1) modulesData[idx].topic = chosenTopic;

            logActivity('Sent to downloads', `"${m.title}" → ${chosenTopic}`, 'module');
            renderModules();

            const topicShort = chosenTopic.replace('Module ', 'Mod ').replace(': ', ' — ');
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Sent to downloads!',
                html: `<p style="font-size:13px;color:#6b7280;font-family:'Plus Jakarta Sans',sans-serif">
                           <strong style="color:#111827">${Security.escape(m.title)}</strong> is now visible
                           to students under <strong style="color:#2563eb">${Security.escape(topicShort)}</strong>.
                       </p>`,
                confirmButtonColor: '#2563eb',
                timer: 3000,
                timerProgressBar: true,
            });

        } catch (err) {
            warn('Failed', err.message || 'Could not update the module. Please try again.');
        }
    });
}

function topicTheme(topic) {
    if (topic.includes('Module 1') || topic.includes('Sequences')) return 'blue-theme';
    if (topic.includes('Module 2') || topic.includes('Polynomials')) return 'green-theme';
    if (topic.includes('Module 3') || topic.includes('Advanced')) return 'orange-theme';
    return 'blue-theme';
}

/* ============================================================
   FILE UPLOAD HANDLER
   ============================================================ */
// Global clearFile function for file removal
function clearFile() {
    pendingFile = null;
    const fileInput = document.getElementById('mod-file');
    const uploadArea = document.getElementById('mod-file-area');
    const preview = document.getElementById('mod-file-preview');
    
    if (fileInput) fileInput.value = '';
    if (preview) preview.classList.remove('visible');
    if (uploadArea) {
        uploadArea.style.display = '';
        uploadArea.style.removeProperty('display');
    }
}

function initFileUpload() {
    const fileInput   = document.getElementById('mod-file');
    const uploadArea  = document.getElementById('mod-file-area');
    const preview     = document.getElementById('mod-file-preview');
    const previewName = document.getElementById('mod-file-name');
    const previewSize = document.getElementById('mod-file-size');
    const removeBtn   = document.getElementById('mod-file-remove');

    if (!fileInput) return;

    function fmtSize(bytes) {
        if (bytes < 1024)    return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
    function showPreview(file) {
        pendingFile = file;
        previewName.textContent = file.name;
        previewSize.textContent = fmtSize(file.size);
        preview.classList.add('visible');
        uploadArea.style.display = 'none';
    }

    fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files[0]) showPreview(fileInput.files[0]);
    });
    removeBtn.addEventListener('click', clearFile);
    uploadArea.addEventListener('dragover',  e => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
    uploadArea.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) showPreview(file);
    });
}

/* ============================================================
   PROFILE
   ============================================================ */
function renderProfile() {
    const actEl = document.getElementById('profile-activity');
    if (!activity.length) {
        actEl.innerHTML = `<div class="empty-state"><div class="empty-icon">📋</div><h4>No recent activity</h4><p>Your actions will appear here.</p></div>`;
        return;
    }
    actEl.innerHTML = activity.slice(0, 5).map(a => `
        <div class="student-item" style="cursor:default">
            <div class="student-info">
                <div class="student-avatar" style="background:linear-gradient(135deg,#60a5fa,#2563eb)">${Security.escape(initials(a.title))}</div>
                <div>
                    <div class="student-name">${Security.escape(a.title)}</div>
                    <div class="student-meta">${Security.escape(a.sub)}</div>
                </div>
            </div>
            <span class="status-badge badge-new">${Security.escape(a.time)}</span>
        </div>`).join('');
}

function clearPasswordForm() {
    ['pw-current', 'pw-new', 'pw-confirm'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
}

async function updatePassword() {
    const current = document.getElementById('pw-current')?.value || '';
    const next = document.getElementById('pw-new')?.value || '';
    const confirm = document.getElementById('pw-confirm')?.value || '';

    const res = await fetch('/teacher/account/password', {
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
    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        return warn('Could not update password', firstError || data.message || 'Please check your input and try again.');
    }

    toast('success', 'Password updated successfully!');
    clearPasswordForm();
}

/* ============================================================
   ACTIVITY LOG
   ============================================================ */

/** This teacher's local activity `type` values → the admin dashboard's shared taxonomy. */
const ADMIN_ACTIVITY_TYPE = {
    settings: 'system',
    success:  'content',
    report:   'content',
    module:   'content',
    quiz:     'content',
};

function logActivity(title, sub, type) {
    activity.unshift({ title, sub, type: type || 'general', time: 'just now', ts: Date.now() });
    if (activity.length > 30) activity.pop();

    // Best-effort: also post to the shared activity_logs table so this
    // shows up on the admin dashboard's Activity tab. Never block the
    // teacher's own UI on this — it's observability, not a requirement.
    const teacherName = window.__USER__?.name || 'A teacher';
    sbInsert(ACTIVITY_TABLE, {
        type:  ADMIN_ACTIVITY_TYPE[type] || 'system',
        title: `${title} (Teacher)`,
        sub:   `${sub} — by ${teacherName}`,
        badge: 'Teacher',
    }).catch(e => console.warn('Could not sync activity to admin log:', e.message));
}

/* ============================================================
   MODALS
   ============================================================ */
function openModal(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }

/* ============================================================
   LOGOUT
   ============================================================ */
function confirmLogout() {
    Swal.fire({
        title: 'Are you sure?', text: 'You will be logged out of your account.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#2563eb', cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, logout!', cancelButtonText: 'Cancel',
    }).then(r => {
        if (r.isConfirmed) {
            Swal.fire({ icon: 'success', title: 'Logged out', text: 'Goodbye!', timer: 1500, timerProgressBar: true, showConfirmButton: false })
                .then(() => document.getElementById('logout-form').submit());
        }
    });
}

/* ============================================================
   UTILITIES
   ============================================================ */
function setText(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
function initials(str)    { return (str || '').split(' ').slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || '?'; }
function dateNow()        { return new Date().toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }); }
function formatDate(iso) {
    if (!iso) return dateNow();
    try { return new Date(iso).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }); }
    catch { return dateNow(); }
}
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
function avgProgress() {
    if (!students.length) return 0;
    return Math.round(students.reduce((sum, s) => sum + s.progress, 0) / students.length);
}
function badgeClass(status) {
    return { Excellent: 'badge-excellent', Good: 'badge-good', Average: 'badge-average', 'Needs Help': 'badge-needs-help' }[status] || 'badge-needs-help';
}
function progressColor(pct) {
    if (pct >= 80) return '#2563eb';
    if (pct >= 60) return '#10b981';
    if (pct >= 40) return '#f97316';
    return '#ef4444';
}
function makePgBtn(label, disabled, handler) {
    const btn = document.createElement('button');
    btn.className = 'pg-btn'; btn.textContent = label; btn.disabled = disabled;
    btn.addEventListener('click', handler);
    return btn;
}
function warn(title, text)  { Swal.fire({ icon: 'warning', title, text, confirmButtonColor: '#2563eb' }); }
function toast(icon, title) { Swal.fire({ icon, title, timer: 2000, timerProgressBar: true, showConfirmButton: false }); }
function safeParseJSON(str, fallback) {
    if (!str) return fallback;
    try { return JSON.parse(str); } catch { return fallback; }
}

/* ============================================================
   QUIZ GENERATOR
   ============================================================ */

/* ------------------------------------------------------------------
   ACTIVITY OPTIONS per module topic
------------------------------------------------------------------ */
const ACTIVITY_OPTIONS = {
    sequences: [
        { value: 'arithmetic_sequence',  label: 'Activity 1: Arithmetic Sequence' },
        { value: 'geometric_sequence',   label: 'Activity 2: Geometric Sequence' },
        { value: 'harmonic_sequence',    label: 'Activity 3: Harmonic Sequence' },
        { value: 'fibonacci_sequence',   label: 'Activity 4: Fibonacci Sequence' },
        { value: 'finite_infinite',      label: 'Activity 5: Finite and Infinite Sequences' },
    ],
    polynomials: [
        { value: 'division_polynomials', label: 'Activity 1: Division of Polynomials' },
        { value: 'remainder_theorem',    label: 'Activity 2: Remainder & Factor Theorem' },
        { value: 'polynomial_equations', label: 'Activity 3: Polynomial Equations' },
    ],
    advanced: [
        { value: 'rational_equations',    label: 'Activity 1: Rational Equations' },
        { value: 'radical_equations',     label: 'Activity 2: Radical Equations' },
        { value: 'exponential_functions', label: 'Activity 3: Exponential Functions' },
        { value: 'logarithmic_functions', label: 'Activity 4: Logarithmic Functions' },
    ],
};

const TOPIC_LABELS = {
    sequences:   'Module 1: Sequences and Series',
    polynomials: 'Module 2: Polynomials',
    advanced:    'Module 3: Advanced Equations',
};

/* ------------------------------------------------------------------
   updateActivityOptions — called when topic dropdown changes
------------------------------------------------------------------ */
function updateActivityOptions() {
    const topic   = document.getElementById('quiz-topic').value;
    const actSel  = document.getElementById('quiz-activity');
    const options = ACTIVITY_OPTIONS[topic] || [];
    actSel.innerHTML = options.map(o =>
        `<option value="${o.value}">${o.label}</option>`
    ).join('');
}

/* ------------------------------------------------------------------
   switchQuizTab
------------------------------------------------------------------ */
function switchQuizTab(tab) {
    ['pretest', 'activity', 'posttest'].forEach(t => {
        const panel = document.getElementById('panel-' + t);
        const btn   = document.getElementById('tab-'   + t);
        if (panel) panel.style.display = 'none';
        if (btn)   btn.classList.remove('active');
    });
    const activePanel = document.getElementById('panel-' + tab);
    const activeBtn   = document.getElementById('tab-'   + tab);
    if (activePanel) activePanel.style.display = '';
    if (activeBtn)   activeBtn.classList.add('active');
}

/* ------------------------------------------------------------------
   activityValueToTopicKey — maps activity value → MQ_TOPICS key
   sa module.blade.php
------------------------------------------------------------------ */
function activityValueToTopicKey(actVal) {
    const map = {
        arithmetic_sequence:  'ari',
        geometric_sequence:   'geo',
        harmonic_sequence:    'har',
        fibonacci_sequence:   'fib',
        finite_infinite:      'fin',
        division_polynomials: 'div',
        remainder_theorem:    'rem',
        polynomial_equations: 'poly',
        rational_equations:   'rat',
        radical_equations:    'rad',
        exponential_functions:'exp',
        logarithmic_functions:'log',
    };
    return map[actVal] || null;
}

/* ------------------------------------------------------------------
   getQuizCounts — Returns teacher-configured item counts
   Falls back to defaults if inputs don't exist
------------------------------------------------------------------ */
function getQuizCounts() {
    const preCount  = parseInt(document.getElementById('quiz-count-pre')?.value,  10) || 15;
    const actCount  = parseInt(document.getElementById('quiz-count-act')?.value,  10) || 5;
    const postCount = parseInt(document.getElementById('quiz-count-post')?.value, 10) || 15;

    return {
        pre:  Math.max(1, Math.min(preCount,  30)),
        act:  Math.max(1, Math.min(actCount,  10)),
        post: Math.max(1, Math.min(postCount, 30)),
    };
}

/* ------------------------------------------------------------------
   buildQuizPrompt — accepts a "section" param: 'pretest' | 'posttest'
   Now also accepts `count` to generate the teacher-specified number
------------------------------------------------------------------ */
function buildQuizPrompt(topic, activity, grade, difficulty, section, count) {
    return `You are a Philippine Grade 10 math teacher.
Generate exactly ${count} ${section} multiple-choice questions about "${activity}" under "${topic}".
Difficulty: ${difficulty}.

Return ONLY a valid JSON array. No markdown, no explanation, no backticks.

[
  {
    "question": "...",
    "options": {"A": "...", "B": "...", "C": "...", "D": "..."},
    "answer": "B"
  }
]

Rules:
- Exactly ${count} items
- Answers must vary (not always A)
- Questions must be math-focused and grade-appropriate`;
}

/* ------------------------------------------------------------------
   generateQuizWithRetry — Call OpenRouter API with automatic retry
   on rate limit. Max 3 attempts with backoff (2s, 5s, 10s).
   Fetches API key from backend for security.
   max_tokens set to 3500 to stay within OpenRouter free credit limit.
------------------------------------------------------------------ */
async function generateQuizWithRetry(prompt, maxRetries = 3) {
    let lastError = null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    for (let attempt = 0; attempt < maxRetries; attempt++) {
        try {
            if (attempt > 0) {
                const delays = [2000, 5000, 10000];
                const delay = delays[Math.min(attempt - 1, delays.length - 1)];
                const subtitle = document.getElementById('loading-subtitle');
                if (subtitle) subtitle.textContent = `Rate limit — retrying in ${delay / 1000}s… (${attempt}/${maxRetries})`;
                await new Promise(resolve => setTimeout(resolve, delay));
            }

            // The AI call itself happens server-side; the API key never
            // reaches the browser. See QuizController::generateText().
            const response = await fetch('/teacher/quiz/generate-text', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ prompt }),
            });

            if (response.status === 429) {
                lastError = new Error('Rate limit reached.');
                continue;
            }
            if (!response.ok) {
                const errBody = await response.json().catch(() => ({}));
                if (response.status >= 500) {
                    lastError = new Error(`Server error (${response.status})`);
                    continue;
                }
                throw new Error(errBody.message || `API error ${response.status}`);
            }

            const data = await response.json();
            const raw  = data.content || '';
            if (!raw.trim()) { lastError = new Error('Empty response from API.'); continue; }

            return raw;

        } catch (err) {
            lastError = err;
            console.error(`Attempt ${attempt + 1} failed:`, err.message);
            if (!err.message.includes('rate') && !err.message.includes('Server error') && !err.message.includes('Empty')) throw err;
        }
    }

    throw lastError || new Error('Quiz generation failed after retries. Please try again.');
}

/* ------------------------------------------------------------------
   generateQuiz — makes TWO separate API calls (pretest + posttest)
   to avoid token truncation that caused missing posttest questions.
   Now uses teacher-configured item counts.
------------------------------------------------------------------ */
async function generateQuiz() {
    const topicKey      = document.getElementById('quiz-topic').value;
    const actVal        = document.getElementById('quiz-activity').value;
    const difficulty    = document.getElementById('quiz-difficulty').value || 'medium';
    const grade         = 'Grade 10';
    const topicLabel    = TOPIC_LABELS[topicKey] || topicKey;
    const activityLabel = (ACTIVITY_OPTIONS[topicKey] || [])
                            .find(o => o.value === actVal)?.label || actVal;

    const counts = getQuizCounts();

    setQuizUIState('loading');

    try {
        const subtitle = document.getElementById('loading-subtitle');

        // --- Call 1: Pre-test ---
        if (subtitle) subtitle.textContent = `Generating ${counts.pre} pre-test questions…`;
        const rawPre = await generateQuizWithRetry(
            buildQuizPrompt(topicLabel, activityLabel, grade, difficulty, 'pretest', counts.pre)
        );

        // --- Call 2: Post-test ---
        if (subtitle) subtitle.textContent = `Generating ${counts.post} post-test questions…`;
        const rawPost = await generateQuizWithRetry(
            buildQuizPrompt(topicLabel, activityLabel, grade, difficulty, 'posttest', counts.post)
        );

        const pretest  = parseQuizArray(rawPre);
        const posttest = parseQuizArray(rawPost);

        const minPre  = Math.floor(counts.pre  * 0.6);
        const minPost = Math.floor(counts.post * 0.6);

        if (!pretest  || pretest.length  < minPre)
            throw new Error(`Pre-test only returned ${pretest?.length ?? 0} questions. Please retry.`);
        if (!posttest || posttest.length < minPost)
            throw new Error(`Post-test only returned ${posttest?.length ?? 0} questions. Please retry.`);

        // Pad or trim to exact counts
        const pad = (arr, n) => {
            const out = arr.slice(0, n);
            while (out.length < n) out.push(arr[out.length % arr.length]);
            return out;
        };

        // Build activity items — respect actCount
        const allActivity = buildHardcodedActivity(actVal);
        const activityItems = getRandomItems(allActivity, counts.act);

        currentQuiz = {
            id:            Date.now(),
            topicKey,
            topicLabel,
            activityLabel,
            grade,
            difficulty,
            createdAt:     dateNow(),
            pretest:       pad(pretest,  counts.pre),
            posttest:      pad(posttest, counts.post),
            activity:      activityItems,
            counts,
        };

        // Save to localStorage for module.blade.php
        const mqKey = activityValueToTopicKey(actVal);
        if (mqKey) {
            const toMQFormat = (q) => ({
                q:       q.question,
                choices: Object.values(q.options || {}),
                ans:     Object.keys(q.options || {}).indexOf(q.answer),
                exp:     '',
            });
            localStorage.setItem('mqGeneratedQuiz_' + mqKey, JSON.stringify({
                pre:  currentQuiz.pretest.map(toMQFormat),
                post: currentQuiz.posttest.map(toMQFormat),
            }));
        }

        setQuizUIState('results');
        renderQuizResults(currentQuiz);
        updateQuizMetrics();

    } catch (err) {
        console.error('Quiz generation error:', err);
        setQuizUIState('error', err.message);
    }
}

/* ------------------------------------------------------------------
   parseQuizArray — extracts a JSON array from AI response.
   Replaces the old parseQuizJSON; each API call now returns an array.
------------------------------------------------------------------ */
function parseQuizArray(raw) {
    try {
        console.log('Raw length:', raw.length, '| Preview:', raw.substring(0, 120));

        // Strip markdown fences
        let clean = raw.replace(/```json\s*/gi, '').replace(/```\s*/g, '').trim();

        // Extract the first [...] block
        const start = clean.indexOf('[');
        const end   = clean.lastIndexOf(']');
        if (start === -1 || end === -1 || end <= start) {
            console.error('No JSON array found. Full raw:', raw);
            return null;
        }

        const arr = JSON.parse(clean.slice(start, end + 1));
        if (!Array.isArray(arr) || arr.length === 0) {
            console.error('Parsed value is not a non-empty array');
            return null;
        }

        // Randomize answer positions to prevent bias
        return arr.map(q => {
            if (!q.options || typeof q.options !== 'object') return q;
            const answerValue = q.options[q.answer];
            if (!answerValue) return q;
            const shuffled = Object.entries(q.options).sort(() => Math.random() - 0.5);
            const newOptions = {};
            let newAnswer = '';
            shuffled.forEach(([, val], idx) => {
                const key = String.fromCharCode(65 + idx);
                newOptions[key] = val;
                if (val === answerValue) newAnswer = key;
            });
            return { ...q, options: newOptions, answer: newAnswer };
        });

    } catch (e) {
        console.error('JSON parse error:', e.message, '| Raw:', raw.substring(0, 400));
        return null;
    }
}

/* ------------------------------------------------------------------
   getRandomItems — randomly select items from an array
------------------------------------------------------------------ */
function getRandomItems(array, count) {
    if (!Array.isArray(array) || array.length === 0) return [];
    const shuffled = [...array].sort(() => Math.random() - 0.5);
    return shuffled.slice(0, Math.min(count, array.length));
}

/* ------------------------------------------------------------------
   buildHardcodedActivity — 5 classroom activity items per activity
------------------------------------------------------------------ */
function buildHardcodedActivity(activityValue) {
    const activities = {
        arithmetic_sequence: [
            { question: 'Find the 10th term of the arithmetic sequence: 3, 7, 11, 15, …', instruction: 'Show complete solution using the formula aₙ = a₁ + (n−1)d.' },
            { question: 'Find the common difference of an arithmetic sequence where a₁ = 5 and a₈ = 33.', instruction: 'Use d = (aₙ − a₁) / (n − 1) and show all steps.' },
            { question: 'Insert 3 arithmetic means between 4 and 24.', instruction: 'Find the common difference first, then list the complete sequence.' },
            { question: 'The 4th term of an arithmetic sequence is 18 and the 9th term is 38. Find a₁.', instruction: 'Set up a system of equations using aₙ = a₁ + (n−1)d.' },
            { question: 'Find the sum of the first 20 terms: 2, 5, 8, 11, …', instruction: 'Use Sₙ = n/2 × (2a₁ + (n−1)d).' },
        ],
        geometric_sequence: [
            { question: 'Find the 7th term of the geometric sequence: 2, 6, 18, 54, …', instruction: 'Use aₙ = a₁ · rⁿ⁻¹ and show complete solution.' },
            { question: 'Find the common ratio of a geometric sequence where a₁ = 3 and a₅ = 48.', instruction: 'Use r = ⁿ⁻¹√(aₙ / a₁).' },
            { question: 'Insert 2 geometric means between 4 and 108.', instruction: 'Find the common ratio first, then write the complete sequence.' },
            { question: 'Find the sum of the first 6 terms: 1, 3, 9, 27, …', instruction: 'Use Sₙ = a₁(rⁿ − 1) / (r − 1).' },
            { question: 'A bouncing ball reaches 80% of its previous height. If dropped from 5 m, what is the height on the 4th bounce?', instruction: 'Identify a₁ and r, then apply the geometric sequence formula.' },
        ],
        harmonic_sequence: [
            { question: 'Is 1/2, 1/5, 1/8, 1/11 a harmonic sequence? Justify your answer.', instruction: 'Check if the reciprocals form an arithmetic sequence.' },
            { question: 'Find the next two terms of: 1/3, 1/7, 1/11, …', instruction: 'Find the arithmetic sequence of reciprocals, then convert back.' },
            { question: 'Find the 5th term of the harmonic sequence whose arithmetic reciprocals start with 2 and have d = 3.', instruction: 'Write the arithmetic sequence, find the 5th term, then take its reciprocal.' },
            { question: 'Insert one harmonic mean between 1/4 and 1/12.', instruction: 'Work with the reciprocals to insert the arithmetic mean, then convert.' },
            { question: 'The 2nd and 5th terms of a harmonic sequence are 1/5 and 1/11. Find the 8th term.', instruction: 'Find the arithmetic sequence of reciprocals and solve for d.' },
        ],
        fibonacci_sequence: [
            { question: 'Write the first 12 terms of the Fibonacci sequence starting with 1, 1.', instruction: 'Each term is the sum of the two preceding terms: Fₙ = Fₙ₋₁ + Fₙ₋₂.' },
            { question: 'The 7th Fibonacci number is 13 and the 8th is 21. What is the 9th?', instruction: 'Apply the Fibonacci recurrence relation.' },
            { question: 'Find the ratio F₁₀ / F₉ where F₉ = 34. How close is it to the golden ratio (φ ≈ 1.618)?', instruction: 'Calculate the ratio and compare. Discuss why consecutive Fibonacci ratios approach φ.' },
            { question: 'A pair of rabbits produces one pair per month. Starting with 1 pair, how many pairs are there after 10 months?', instruction: 'Model using the Fibonacci sequence and list the monthly counts.' },
            { question: 'True or False: Every 3rd Fibonacci number is even. Provide evidence using the first 12 terms.', instruction: 'List the terms and identify which positions are even.' },
        ],
        finite_infinite: [
            { question: 'Classify each sequence as finite or infinite: (a) months in a year, (b) counting numbers, (c) test scores in a class.', instruction: 'Explain your classification for each with a mathematical justification.' },
            { question: 'Give the first 4 terms of the infinite sequence defined by aₙ = 2n + 1.', instruction: 'Substitute n = 1, 2, 3, 4 into the formula.' },
            { question: 'The sequence 5, 10, 15, …, 100 is finite. How many terms does it have?', instruction: 'Use aₙ = a₁ + (n−1)d and solve for n.' },
            { question: 'Write a formula for the infinite sequence: 4, 7, 10, 13, …', instruction: 'Identify a₁ and d, then write aₙ in terms of n.' },
            { question: 'Does the infinite sequence 1/n converge or diverge as n → ∞? Explain.', instruction: 'Evaluate limₙ→∞ (1/n) and interpret the result.' },
        ],
        division_polynomials: [
            { question: 'Divide (3x³ + 5x² − 2x + 1) by (x + 2) using long division.', instruction: 'Show all steps of the polynomial long division algorithm.' },
            { question: 'Use synthetic division to divide (x³ − 4x + 6) by (x − 2).', instruction: 'Write the coefficients, apply synthetic division, and state the quotient and remainder.' },
            { question: 'Divide (4x⁴ − 8x² + 3) by (2x² − 1).', instruction: 'Use polynomial long division and verify by multiplying back.' },
            { question: 'Find the quotient and remainder when (x⁴ + 1) is divided by (x + 1).', instruction: 'Use synthetic division with zero coefficients for missing terms.' },
            { question: 'What is the remainder when (2x³ − 3x + 5) is divided by (x − 3)?', instruction: 'Use the Remainder Theorem: evaluate P(3).' },
        ],
        remainder_theorem: [
            { question: 'Find the remainder when P(x) = x³ − 2x² + 3x − 4 is divided by (x − 2).', instruction: 'Apply the Remainder Theorem: compute P(2).' },
            { question: 'Is (x + 3) a factor of x³ + 2x² − 5x − 6? Show work.', instruction: 'Use the Factor Theorem: check if P(−3) = 0.' },
            { question: 'Find the value of k so that (x − 1) is a factor of 2x³ − kx² + 3x − 1.', instruction: 'Substitute x = 1 into P(x) = 0 and solve for k.' },
            { question: 'Verify the Factor Theorem for P(x) = x³ − 8 and (x − 2).', instruction: 'Compute P(2) and confirm it equals zero, then perform the division.' },
            { question: 'Find all factors of P(x) = x³ − 6x² + 11x − 6.', instruction: 'Use the Factor Theorem with rational roots ±1, ±2, ±3, ±6, then factor completely.' },
        ],
        polynomial_equations: [
            { question: 'Solve: x³ − 3x² − 4x + 12 = 0.', instruction: 'Factor by grouping or use the Factor Theorem to find rational roots.' },
            { question: 'Find all zeros of P(x) = x⁴ − 5x² + 4.', instruction: 'Substitute u = x² to reduce to a quadratic, then solve.' },
            { question: 'Use the Rational Zero Theorem to list possible rational roots of 2x³ − 5x² + 1.', instruction: 'List all factors of the constant term over factors of the leading coefficient.' },
            { question: 'Solve: x⁴ − 10x² + 9 = 0.', instruction: 'Factor as a quadratic in x², then solve each quadratic factor.' },
            { question: 'Find a polynomial with roots x = −1, 2, and 3.', instruction: 'Write (x + 1)(x − 2)(x − 3) and expand completely.' },
        ],
        rational_equations: [
            { question: 'Solve: (x + 1)/(x − 2) = 3/4. Check for extraneous solutions.', instruction: 'Cross-multiply, solve, and verify that the solution does not make the denominator zero.' },
            { question: 'Solve: 1/x + 1/(x + 2) = 1/3.', instruction: 'Multiply both sides by the LCD and solve the resulting quadratic.' },
            { question: 'A pipe can fill a tank in 6 hours and another in 9 hours. How long together?', instruction: 'Set up a rational equation: 1/6 + 1/9 = 1/t and solve for t.' },
            { question: 'Solve: (2x)/(x + 1) − 1 = 3/(x + 1).', instruction: 'Multiply through by (x + 1) and check for restrictions.' },
            { question: 'Solve: x/(x − 3) + 2/(x + 3) = 12/(x² − 9).', instruction: 'Factor the denominator, multiply by LCD, and check for extraneous solutions.' },
        ],
        radical_equations: [
            { question: 'Solve: √(3x + 1) = 4. Check your answer.', instruction: 'Square both sides, solve for x, and verify by substitution.' },
            { question: 'Solve: √(x + 5) = x − 1.', instruction: 'Isolate the radical, square both sides, solve the quadratic, and check for extraneous roots.' },
            { question: 'Solve: ∛(2x − 3) = −1.', instruction: 'Cube both sides and solve. No extraneous roots for cube roots.' },
            { question: 'Solve: √(x + 3) + √(x) = 3.', instruction: 'Isolate one radical, square both sides, then isolate and square again.' },
            { question: 'For what values of x is √(5x − 10) defined?', instruction: 'Set 5x − 10 ≥ 0 and solve the inequality.' },
        ],
        exponential_functions: [
            { question: 'Evaluate: 3^(2x) = 81. Find x.', instruction: 'Rewrite 81 as a power of 3, equate exponents, and solve.' },
            { question: 'The population of a town doubles every 5 years. Starting at 10,000, what is the population after 20 years?', instruction: 'Use P = P₀ · 2^(t/5) and substitute.' },
            { question: 'Solve: 5^x = 125^(x−2).', instruction: 'Rewrite with the same base and solve for x.' },
            { question: 'Sketch the graph of y = 2^x and y = (1/2)^x. Compare their behaviors.', instruction: 'Create a table of values for x = −2, −1, 0, 1, 2 for each function.' },
            { question: 'A ₱5,000 investment earns 4% compounded annually. Write and evaluate the function for 10 years.', instruction: 'Use A = P(1 + r)^t with P = 5000, r = 0.04, t = 10.' },
        ],
        logarithmic_functions: [
            { question: 'Evaluate: log₂(64).', instruction: 'Ask: 2 to what power equals 64? Express as 2^n = 64.' },
            { question: 'Solve: log₃(x) = 4.', instruction: 'Rewrite in exponential form: 3^4 = x.' },
            { question: 'Expand: log(x²y / z³) using logarithm properties.', instruction: 'Apply product, quotient, and power rules of logarithms.' },
            { question: 'Solve: log₂(x) + log₂(x − 2) = 3.', instruction: 'Combine logarithms, rewrite in exponential form, and check for valid domain.' },
            { question: 'The intensity of an earthquake is I. Find the magnitude on the Richter scale: M = log(I/I₀) when I = 10,000·I₀.', instruction: 'Substitute and simplify using log properties.' },
        ],
        systems_equations: [
            { question: 'Solve by substitution: y = 2x + 1 and 3x + y = 11.', instruction: 'Substitute the first equation into the second and solve for x, then find y.' },
            { question: 'Solve by elimination: 2x + 3y = 12 and 4x − y = 2.', instruction: 'Multiply one equation to align coefficients, then add/subtract.' },
            { question: 'A school sold 200 tickets. Adult tickets cost ₱80 and student tickets ₱50. Total sales: ₱13,000. How many of each?', instruction: 'Set up a system: x + y = 200 and 80x + 50y = 13000.' },
            { question: 'Solve: x² + y² = 25 and x + y = 7 (system with quadratic).', instruction: 'Express y in terms of x from the linear equation, substitute into the circle equation.' },
            { question: 'Determine if the system is consistent, inconsistent, or dependent: 3x − y = 5 and 6x − 2y = 10.', instruction: 'Compare the ratios of coefficients and constants.' },
        ],
    };

    const activityList = activities[activityValue] || [
        { question: 'Describe the main concept of this activity in your own words.', instruction: 'Write at least 3 sentences using mathematical vocabulary.' },
        { question: 'Give a real-life example that applies this mathematical concept.', instruction: 'Include numbers and explain how the concept is used.' },
        { question: 'Solve a problem of your own creation using this concept.', instruction: 'Write the problem, show full solution, and verify your answer.' },
        { question: 'What are the most important formulas or rules for this topic?', instruction: 'List and briefly explain each formula or rule.' },
        { question: 'Create a concept map connecting the ideas in this activity.', instruction: 'Draw or describe the map with at least 5 connected concepts.' },
    ];

    return getRandomItems(activityList, 5);
}

/* ------------------------------------------------------------------
   setQuizUIState — manage visibility of loading/error/results
------------------------------------------------------------------ */
function setQuizUIState(state, errorMsg = '') {
    const genBtn     = document.getElementById('quiz-gen-btn');
    const loading    = document.getElementById('quiz-loading');
    const errorEl    = document.getElementById('quiz-error');
    const errorMsgEl = document.getElementById('quiz-error-msg');
    const results    = document.getElementById('quiz-results-wrapper');

    loading.classList.remove('visible');
    errorEl.classList.remove('visible');
    if (results) results.style.display = 'none';
    if (genBtn)  genBtn.disabled = false;

    if (state === 'loading') {
        loading.classList.add('visible');
        if (genBtn) genBtn.disabled = true;
        cycleLoadingMessages();
    } else if (state === 'error') {
        errorEl.classList.add('visible');
        if (errorMsgEl) errorMsgEl.textContent = errorMsg || 'Something went wrong. Please try again.';
    } else if (state === 'results') {
        if (results) results.style.display = '';
    }
}

/* ------------------------------------------------------------------
   cycleLoadingMessages
------------------------------------------------------------------ */
const LOADING_MSGS = [
    'Calling AI…',
    'Crafting pre-test questions…',
    'Generating post-test questions…',
    'Almost there — finalizing quiz…',
];

let _loadingTimer = null;

function cycleLoadingMessages() {
    const el = document.getElementById('loading-subtitle');
    if (!el) return;
    let i = 0;
    el.textContent = LOADING_MSGS[0];
    clearInterval(_loadingTimer);
    _loadingTimer = setInterval(() => {
        i = (i + 1) % LOADING_MSGS.length;
        el.textContent = LOADING_MSGS[i];
    }, 2200);
}

/* ------------------------------------------------------------------
   renderQuizResults — populate the three panels
   Now shows item counts in tab labels and doesn't shuffle for editing
------------------------------------------------------------------ */
function renderQuizResults(quiz) {
    clearInterval(_loadingTimer);

    const label = document.getElementById('quiz-result-label');
    const sub   = document.getElementById('quiz-result-sub');
    if (label) label.textContent = `Quiz — ${quiz.activityLabel}`;
    if (sub)   sub.textContent   =
        `${quiz.grade} · ${quiz.difficulty} · Generated ${quiz.createdAt} · `
      + `Pre: ${quiz.pretest.length} · Act: ${quiz.activity.length} · Post: ${quiz.posttest.length}`;

    // Update tab labels with actual counts
    const tabPre  = document.getElementById('tab-pretest');
    const tabAct  = document.getElementById('tab-activity');
    const tabPost = document.getElementById('tab-posttest');
    if (tabPre)  tabPre.innerHTML  = `📋 Pre-Test <span class="quiz-tab-count">${quiz.pretest.length} items</span>`;
    if (tabAct)  tabAct.innerHTML  = `⚡ Activity <span class="quiz-tab-count">${quiz.activity.length} items</span>`;
    if (tabPost) tabPost.innerHTML = `✅ Post-Test <span class="quiz-tab-count">${quiz.posttest.length} items</span>`;

    renderQuestionList('pretest-questions',  quiz.pretest,  'pretest');
    renderQuestionList('posttest-questions', quiz.posttest, 'posttest');
    renderActivityList('activity-questions', quiz.activity);

    switchQuizTab('pretest');
    document.getElementById('quiz-results-wrapper')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* ------------------------------------------------------------------
   renderQuestionList — MCQ rendering with inline editing
   Questions, options, and correct answers are now editable
------------------------------------------------------------------ */
function renderQuestionList(containerId, questions, type) {
    const el = document.getElementById(containerId);
    if (!el || !Array.isArray(questions)) return;

    if (!questions.length) {
        el.innerHTML = `<div class="empty-state"><div class="empty-icon">📭</div><h4>No questions generated</h4><p>Try regenerating the quiz.</p></div>`;
        return;
    }

    el.innerHTML = questions.map((q, idx) => {
        const opts   = q.options || {};
        const ans    = (q.answer || '').toUpperCase();
        const qId    = `${type}-q${idx}`;

        const optionsHTML = Object.entries(opts).map(([letter, text]) => {
            const isCorrect = letter.toUpperCase() === ans;
            return `
            <div class="quiz-option-edit" data-letter="${letter}">
                <label class="quiz-opt-radio-wrap" title="Mark as correct answer">
                    <input type="radio"
                           name="${qId}-ans"
                           value="${letter}"
                           data-qid="${qId}"
                           ${isCorrect ? 'checked' : ''}
                           onchange="quizMarkAnswer(this)">
                    <span class="quiz-opt-radio-dot ${isCorrect ? 'is-correct' : ''}"></span>
                </label>
                <span class="option-letter">${Security.escape(letter)}</span>
                <span class="option-text quiz-editable"
                      contenteditable="true"
                      data-type="${type}"
                      data-idx="${idx}"
                      data-field="option-${letter}"
                      onblur="quizSaveEdit(this)"
                      title="Click to edit option"
                >${Security.escape(text)}</span>
            </div>`;
        }).join('');

        return `
        <div class="quiz-question-item quiz-question-editable" data-type="${type}" data-idx="${idx}">
            <div class="quiz-q-number">${idx + 1}</div>
            <div class="quiz-q-content">
                <div class="quiz-q-text quiz-editable"
                     contenteditable="true"
                     data-type="${type}"
                     data-idx="${idx}"
                     data-field="question"
                     onblur="quizSaveEdit(this)"
                     title="Click to edit question"
                >${Security.escape(q.question)}</div>
                <div class="quiz-q-options">${optionsHTML}</div>
                <div class="quiz-edit-hint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         style="width:11px;height:11px;flex-shrink:0">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Click any text to edit · Select radio to change correct answer
                </div>
            </div>
        </div>`;
    }).join('');
}

/* ------------------------------------------------------------------
   renderActivityList — open-ended / instructional items
   Questions and instructions are now editable
------------------------------------------------------------------ */
function renderActivityList(containerId, activities) {
    const el = document.getElementById(containerId);
    if (!el || !Array.isArray(activities)) return;

    if (!activities.length) {
        el.innerHTML = `<div class="empty-state"><div class="empty-icon">⚡</div><h4>No activity items</h4></div>`;
        return;
    }

    el.innerHTML = activities.map((a, idx) => `
        <div class="quiz-question-item quiz-question-editable" data-type="activity" data-idx="${idx}">
            <div class="quiz-q-number">${idx + 1}</div>
            <div class="quiz-q-content">
                <div class="quiz-q-text quiz-editable"
                     contenteditable="true"
                     data-type="activity"
                     data-idx="${idx}"
                     data-field="question"
                     onblur="quizSaveEdit(this)"
                     title="Click to edit question"
                >${Security.escape(a.question)}</div>
                ${a.instruction !== undefined ? `
                <div class="quiz-q-instruction quiz-editable"
                     contenteditable="true"
                     data-type="activity"
                     data-idx="${idx}"
                     data-field="instruction"
                     onblur="quizSaveEdit(this)"
                     title="Click to edit instruction"
                >📝 ${Security.escape(a.instruction)}</div>` : ''}
                <div class="quiz-edit-hint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         style="width:11px;height:11px;flex-shrink:0">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Click any text to edit
                </div>
            </div>
        </div>`).join('');
}

/* ------------------------------------------------------------------
   quizSaveEdit — called onblur from any editable field
   Updates currentQuiz so saving to Supabase gets the edited version
------------------------------------------------------------------ */
function quizSaveEdit(el) {
    if (!currentQuiz) return;

    const type  = el.dataset.type;   // 'pretest' | 'posttest' | 'activity'
    const idx   = parseInt(el.dataset.idx, 10);
    const field = el.dataset.field;  // 'question' | 'option-A' … | 'instruction'

    const newVal = el.textContent
        .replace(/^📝\s*/, '')   // strip the emoji prefix from instructions
        .trim();

    if (!newVal) { el.textContent = '(empty)'; return; }

    const arr = type === 'pretest'  ? currentQuiz.pretest  :
                type === 'posttest' ? currentQuiz.posttest :
                                     currentQuiz.activity;

    if (!arr || !arr[idx]) return;

    if (field === 'question') {
        arr[idx].question = newVal;
    } else if (field === 'instruction') {
        arr[idx].instruction = newVal;
    } else if (field.startsWith('option-')) {
        const letter = field.split('-')[1];
        if (arr[idx].options) arr[idx].options[letter] = newVal;
    }

    // Subtle flash to confirm save
    el.classList.add('quiz-edit-saved');
    setTimeout(() => el.classList.remove('quiz-edit-saved'), 800);
}

/* ------------------------------------------------------------------
   insertQuizSymbol — math symbol palette for question/option/instruction
   fields. Those fields are contenteditable (not <input>), so insertion
   uses the Selection/Range APIs. The palette buttons call this on
   `mousedown` with preventDefault() so the browser never blurs the
   field the teacher was just editing — document.activeElement and the
   live selection are still that field when this runs.
------------------------------------------------------------------ */
function insertQuizSymbol(symbol) {
    const active = document.activeElement;
    if (!active?.classList?.contains('quiz-editable')) {
        return warn('Click into a field first', 'Tap the question, option, or instruction text you want to edit, then tap a symbol to insert it there.');
    }

    const sel = window.getSelection();
    if (!sel.rangeCount) return;

    const range = sel.getRangeAt(0);
    if (!active.contains(range.commonAncestorContainer)) return;

    range.deleteContents();
    const textNode = document.createTextNode(symbol);
    range.insertNode(textNode);
    range.setStartAfter(textNode);
    range.setEndAfter(textNode);
    sel.removeAllRanges();
    sel.addRange(range);
    active.focus();
}

/* ------------------------------------------------------------------
   MATH SYMBOL PALETTE — categories, search, and per-teacher "recently
   used", all rendered client-side into the #quiz-symbol-palette mount
   point. Insertion itself is still handled by insertQuizSymbol() above.
------------------------------------------------------------------ */
const QUIZ_SYMBOL_GROUPS = [
    { id: 'basic', label: 'Basic', symbols: [
        { char: '+', name: 'plus add' }, { char: '−', name: 'minus subtract' }, { char: '×', name: 'times multiply' },
        { char: '÷', name: 'divide division' }, { char: '=', name: 'equals' }, { char: '≠', name: 'not equal' },
        { char: '±', name: 'plus minus' }, { char: '·', name: 'dot multiply' }, { char: '%', name: 'percent' },
    ]},
    { id: 'comparison', label: 'Comparison', symbols: [
        { char: '<', name: 'less than' }, { char: '>', name: 'greater than' },
        { char: '≤', name: 'less than or equal' }, { char: '≥', name: 'greater than or equal' },
        { char: '≈', name: 'approximately equal' }, { char: '≡', name: 'equivalent identical' }, { char: '∝', name: 'proportional to' },
    ]},
    { id: 'powers', label: 'Powers & Roots', symbols: [
        { char: '²', name: 'squared power 2' }, { char: '³', name: 'cubed power 3' }, { char: 'ⁿ', name: 'nth power exponent n' },
        { char: '√', name: 'square root' }, { char: '∛', name: 'cube root' }, { char: 'ⁿ√', name: 'nth root' },
    ]},
    { id: 'fractions', label: 'Fractions', symbols: [
        { char: '½', name: 'one half fraction' }, { char: '⅓', name: 'one third fraction' }, { char: '⅔', name: 'two thirds fraction' },
        { char: '¼', name: 'one quarter fraction' }, { char: '¾', name: 'three quarters fraction' }, { char: '⅛', name: 'one eighth fraction' },
    ]},
    { id: 'greek', label: 'Greek', symbols: [
        { char: 'π', name: 'pi' }, { char: 'α', name: 'alpha' }, { char: 'β', name: 'beta' }, { char: 'γ', name: 'gamma' },
        { char: 'θ', name: 'theta' }, { char: 'λ', name: 'lambda' }, { char: 'μ', name: 'mu' }, { char: 'σ', name: 'sigma' },
        { char: 'Δ', name: 'delta' }, { char: 'Ω', name: 'omega' },
    ]},
    { id: 'sets', label: 'Sets & Logic', symbols: [
        { char: '∈', name: 'element of belongs to' }, { char: '∉', name: 'not element of' }, { char: '⊂', name: 'subset of' },
        { char: '⊆', name: 'subset or equal' }, { char: '∪', name: 'union' }, { char: '∩', name: 'intersection' },
        { char: '∅', name: 'empty set null' }, { char: '∀', name: 'for all' }, { char: '∃', name: 'there exists' },
        { char: '∴', name: 'therefore' }, { char: '∵', name: 'because' },
    ]},
    { id: 'geometry', label: 'Geometry', symbols: [
        { char: '°', name: 'degree' }, { char: '∠', name: 'angle' }, { char: '⊥', name: 'perpendicular' },
        { char: '∥', name: 'parallel' }, { char: '△', name: 'triangle' }, { char: '≅', name: 'congruent' }, { char: '~', name: 'similar tilde' },
    ]},
    { id: 'calc', label: 'Calculus & Stats', symbols: [
        { char: '∑', name: 'sum summation' }, { char: '∏', name: 'product' }, { char: '∫', name: 'integral' },
        { char: '∞', name: 'infinity' }, { char: '∂', name: 'partial derivative' },
        { char: 'x̄', name: 'x bar mean average' }, { char: 'σ²', name: 'variance' },
    ]},
];

let quizSymbolActiveTab = QUIZ_SYMBOL_GROUPS[0].id;
let quizSymbolSearch = '';

function quizSymbolRecentsKey() {
    return `mqQuizRecentSymbols_${window.__USER__?.id ?? 'guest'}`;
}

function getQuizSymbolRecents() {
    try {
        return JSON.parse(localStorage.getItem(quizSymbolRecentsKey()) || '[]');
    } catch {
        return [];
    }
}

function pushQuizSymbolRecent(symbol) {
    const recents = getQuizSymbolRecents().filter(s => s !== symbol);
    recents.unshift(symbol);
    localStorage.setItem(quizSymbolRecentsKey(), JSON.stringify(recents.slice(0, 12)));
}

function quizSymbolMatches(entry, query) {
    const q = query.toLowerCase();
    return entry.char.toLowerCase().includes(q) || entry.name.toLowerCase().includes(q);
}

function renderSymbolPalette() {
    const mount = document.getElementById('quiz-symbol-palette');
    if (!mount) return;

    const query = quizSymbolSearch.trim();
    const recents = getQuizSymbolRecents();

    const tabsHtml = QUIZ_SYMBOL_GROUPS.map(g => `
        <button type="button" class="quiz-symbol-tab ${g.id === quizSymbolActiveTab ? 'active' : ''}"
                onclick="quizSymbolSwitchTab('${g.id}')">${Security.escape(g.label)}</button>
    `).join('');

    const gridEntries = query
        ? QUIZ_SYMBOL_GROUPS.flatMap(g => g.symbols).filter(s => quizSymbolMatches(s, query))
        : (QUIZ_SYMBOL_GROUPS.find(g => g.id === quizSymbolActiveTab)?.symbols ?? []);

    const symbolBtn = s => `
        <button type="button" class="quiz-symbol-btn" title="${Security.escape(s.name)}"
                onmousedown="event.preventDefault(); handleQuizSymbolInsert('${s.char.replace(/'/g, "\\'")}')">${Security.escape(s.char)}</button>`;

    const gridHtml = gridEntries.length
        ? gridEntries.map(symbolBtn).join('')
        : `<span class="quiz-symbol-empty">No symbols match "${Security.escape(query)}".</span>`;

    const recentsHtml = (!query && recents.length) ? `
        <div class="quiz-symbol-recents">
            <span class="quiz-symbol-label">Recent:</span>
            ${recents.map(char => symbolBtn({ char, name: 'recently used' })).join('')}
        </div>` : '';

    mount.innerHTML = `
        <div class="quiz-symbol-palette">
            <input type="text" class="quiz-symbol-search" placeholder="🔍 Search symbols by name (e.g. 'sum', 'pi', 'root')…"
                   value="${Security.escape(query)}" oninput="quizSymbolSetSearch(this.value)"
                   onkeydown="if(event.key==='Escape'){this.value='';quizSymbolSetSearch('');}" maxlength="40">
            ${recentsHtml}
            ${query ? '' : `<div class="quiz-symbol-tabs">${tabsHtml}</div>`}
            <div class="quiz-symbol-grid">${gridHtml}</div>
        </div>`;
}

function quizSymbolSwitchTab(id) {
    quizSymbolActiveTab = id;
    renderSymbolPalette();
}

function quizSymbolSetSearch(value) {
    quizSymbolSearch = value;
    renderSymbolPalette();
    // A full re-render replaces the search input's DOM node, which drops
    // focus/cursor — restore both so typing feels uninterrupted.
    const el = document.querySelector('.quiz-symbol-search');
    if (el) {
        el.focus();
        el.setSelectionRange(el.value.length, el.value.length);
    }
}

function handleQuizSymbolInsert(symbol) {
    insertQuizSymbol(symbol);
    pushQuizSymbolRecent(symbol);
    renderSymbolPalette();
}

/* ------------------------------------------------------------------
   quizMarkAnswer — called when a radio changes
   Updates currentQuiz.pretest/posttest[idx].answer
------------------------------------------------------------------ */
function quizMarkAnswer(radio) {
    if (!currentQuiz) return;

    // qId format: "{type}-q{idx}"
    const qId    = radio.dataset.qid;
    const parts  = qId.split('-q');
    const type   = parts[0];           // 'pretest' | 'posttest'
    const idx    = parseInt(parts[1], 10);
    const letter = radio.value.toUpperCase();

    const arr = type === 'pretest' ? currentQuiz.pretest : currentQuiz.posttest;
    if (arr && arr[idx]) arr[idx].answer = letter;

    // Update dot styles within this question
    const container = radio.closest('.quiz-question-item');
    if (container) {
        container.querySelectorAll('.quiz-opt-radio-dot').forEach(dot => dot.classList.remove('is-correct'));
        radio.closest('.quiz-opt-radio-wrap')
             ?.querySelector('.quiz-opt-radio-dot')
             ?.classList.add('is-correct');
    }
}

/* ------------------------------------------------------------------
   injectQuizEditStyles — add styles for editing affordances
   Call injectQuizEditStyles() once on DOMContentLoaded
------------------------------------------------------------------ */
function injectQuizEditStyles() {
    if (document.getElementById('quiz-edit-styles')) return;
    const style = document.createElement('style');
    style.id = 'quiz-edit-styles';
    style.textContent = `
/* ── Count inputs row ─────────────────────────────────── */
.quiz-count-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
.quiz-count-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
    flex: 1;
    min-width: 120px;
}
.quiz-count-field label {
    font-size: 11px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.quiz-count-field input[type="number"] {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    background: #f9fafb;
    outline: none;
    transition: border-color .15s;
    -moz-appearance: textfield;
}
.quiz-count-field input[type="number"]::-webkit-outer-spin-button,
.quiz-count-field input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; }
.quiz-count-field input[type="number"]:focus { border-color: #2563eb; background: #fff; }
.quiz-count-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    background: #eff6ff;
    color: #2563eb;
    align-self: flex-start;
}

/* ── Editable question cards ──────────────────────────── */
.quiz-question-editable { position: relative; }

.quiz-editable {
    cursor: text;
    border-radius: 6px;
    padding: 3px 6px;
    margin: -3px -6px;
    outline: none;
    transition: background .15s, box-shadow .15s;
    min-height: 1.2em;
    word-break: break-word;
}
.quiz-editable:hover {
    background: rgba(37,99,235,.06);
}
.quiz-editable:focus {
    background: #fff;
    box-shadow: 0 0 0 2px #2563eb55;
}
.quiz-edit-saved {
    background: #d1fae5 !important;
    transition: background .4s;
}

/* ── Option row with radio ────────────────────────────── */
.quiz-option-edit {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 10px;
    border-radius: 8px;
    border: 1.5px solid transparent;
    transition: border-color .15s, background .15s;
}
.quiz-option-edit:hover { background: #f8faff; border-color: #dbeafe; }

.quiz-opt-radio-wrap {
    display: flex;
    align-items: center;
    cursor: pointer;
    flex-shrink: 0;
}
.quiz-opt-radio-wrap input[type="radio"] { display: none; }
.quiz-opt-radio-dot {
    width: 16px; height: 16px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background: #fff;
    transition: border-color .15s, background .15s;
    flex-shrink: 0;
}
.quiz-opt-radio-dot.is-correct {
    border-color: #16a34a;
    background: #16a34a;
    box-shadow: inset 0 0 0 3px #fff;
}
.quiz-opt-radio-wrap:hover .quiz-opt-radio-dot { border-color: #2563eb; }

/* ── Edit hint strip ──────────────────────────────────── */
.quiz-edit-hint {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 8px;
    font-size: 10.5px;
    color: #9ca3af;
    font-weight: 500;
    user-select: none;
}

/* ── Instruction editable override ───────────────────── */
.quiz-q-instruction.quiz-editable {
    display: block;
    margin-top: 6px;
}

/* ── Tab count badge ──────────────────────────────────── */
.quiz-tab-count {
    display: inline-block;
    padding: 2px 6px;
    margin-left: 4px;
    border-radius: 4px;
    font-size: 11px;
    background: rgba(37,99,235,.08);
    color: #2563eb;
    font-weight: 600;
}

/* ── Math symbol palette ──────────────────────────────── */
.quiz-symbol-palette {
    padding: 12px 14px 14px;
    margin-bottom: 14px;
    background: #f8faff;
    border: 1px solid #dbeafe;
    border-radius: 10px;
}
.quiz-symbol-label {
    font-size: 11px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-right: 2px;
}
.quiz-symbol-search {
    width: 100%;
    padding: 8px 12px;
    margin-bottom: 10px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    font-family: inherit;
    font-size: 12.5px;
    color: #111827;
    background: #fff;
    outline: none;
    transition: border-color .15s;
}
.quiz-symbol-search:focus { border-color: #2563eb; }
.quiz-symbol-recents {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    padding-bottom: 10px;
    margin-bottom: 10px;
    border-bottom: 1px dashed #dbeafe;
}
.quiz-symbol-tabs {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.quiz-symbol-tab {
    padding: 5px 10px;
    border-radius: 999px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #6b7280;
    font-family: inherit;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}
.quiz-symbol-tab:hover { border-color: #bfdbfe; color: #2563eb; }
.quiz-symbol-tab.active { background: #2563eb; border-color: #2563eb; color: #fff; }
.quiz-symbol-grid {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.quiz-symbol-empty {
    font-size: 12px;
    color: #9ca3af;
    font-style: italic;
}
.quiz-symbol-btn {
    min-width: 30px;
    height: 28px;
    padding: 0 7px;
    border-radius: 7px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #374151;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
}
.quiz-symbol-btn:hover { background: #eff6ff; border-color: #2563eb; color: #2563eb; }
.quiz-symbol-btn:active { transform: scale(0.92); }
`;
    document.head.appendChild(style);
}

/* ------------------------------------------------------------------
   updateQuizMetrics
------------------------------------------------------------------ */
function updateQuizMetrics() {
    const all   = currentQuiz ? [...savedQuizzes, currentQuiz] : savedQuizzes;
    const total = all.length;
    // Every saved quiz row stores its own pretest/posttest/activity arrays,
    // so count quizzes that actually have items in each section rather than
    // relying on the unused/always-'pretest' `type` tag.
    const pre   = all.filter(q => (q.pretest?.length   ?? 0) > 0).length;
    const post  = all.filter(q => (q.posttest?.length  ?? 0) > 0).length;
    const acts  = all.filter(q => (q.activity?.length  ?? 0) > 0).length;

    setText('qz-total',      total);
    setText('qz-pre',        pre);
    setText('qz-post',       post);
    setText('qz-activities', acts);
    setText('m-quizzes',     total);  // home page counter
}

/* ------------------------------------------------------------------
   saveQuizToSupabase
------------------------------------------------------------------ */
async function saveQuizToSupabase() {
    if (!currentQuiz) return warn('No quiz', 'Please generate a quiz first.');

    try {
        const payload = {
            topic:          currentQuiz.topicLabel,
            activity_label: currentQuiz.activityLabel, // ← include exact topic
            grade:          currentQuiz.grade,
            difficulty:     currentQuiz.difficulty,
            pretest:        JSON.stringify(currentQuiz.pretest),
            posttest:       JSON.stringify(currentQuiz.posttest),
            activity:       JSON.stringify(currentQuiz.activity),
            created_at:     new Date().toISOString(),
        };

        // Check if updating existing (from edit/regen) or inserting new
        const editIdx = currentQuiz._savedIdx ?? window._regenIdx ?? null;
        const existingDbId = currentQuiz._dbId ?? (editIdx !== null ? savedQuizzes[editIdx]?.id : null);

        if (existingDbId) {
            // UPDATE existing row
            await sbUpdate('quizzes', existingDbId, payload);
        } else {
            // INSERT new row
            await sbUpsert('quizzes', [payload]);
        }

        // Clear regen tracker
        window._regenIdx = null;
        if (currentQuiz) currentQuiz._savedIdx = null;

        savedQuizzes.push({ ...currentQuiz, type: 'pretest' });
        updateQuizMetrics();    
        await loadSavedQuizzes();
        toast('success', 'Quiz saved to Supabase! ✅');

        // Ask teacher if they want to publish to students right away
        const { isConfirmed } = await Swal.fire({
            title:             'Publish to students now?',
            html:              `<p style="font-size:13px;color:#6b7280;font-family:'Plus Jakarta Sans',sans-serif">
                                   Saved! Do you also want students to use your new questions
                                   for <strong>${Security.escape(currentQuiz.activityLabel)}</strong>?
                               </p>`,
            icon:              'question',
            showCancelButton:  true,
            confirmButtonColor:'#2563eb',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '📤 Yes, publish',
            cancelButtonText:  'Not yet',
        });

        if (isConfirmed) await publishCurrentQuiz();

    } catch (err) {
        console.error('Save quiz error:', err);
        savedQuizzes.push({ ...currentQuiz });
        updateQuizMetrics();
        renderSavedQuizzes();
        warn('Supabase Save Failed', `${err.message}\n\nThe quiz is saved locally for this session.`);
    }
}

/* ------------------------------------------------------------------
   loadSavedQuizzes
------------------------------------------------------------------ */
async function loadSavedQuizzes() {
    try {
        const rows = await sbSelect('quizzes', '?select=*&order=created_at.desc&limit=20');
        savedQuizzes = (rows || []).map(r => ({
            id:            r.id,
            topicLabel:    r.topic,
            activityLabel: r.activity_label || r.topic, // ← use activity_label if available
            grade:         r.grade,
            difficulty:    r.difficulty,
            createdAt:     formatDate(r.created_at),
            pretest:       safeParseJSON(r.pretest,  []),
            posttest:      safeParseJSON(r.posttest, []),
            activity:      safeParseJSON(r.activity, []),
            type:          'pretest',
        }));
    } catch (err) {
        console.warn('Could not load saved quizzes:', err.message);
        savedQuizzes = [];
    }
    renderSavedQuizzes();
    updateQuizMetrics();
}

/* ------------------------------------------------------------------
   renderSavedQuizzes
------------------------------------------------------------------ */
function renderSavedQuizzes() {
    const el = document.getElementById('saved-quizzes-list');
    if (!el) return;

    if (!savedQuizzes.length) {
        el.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">🗂️</div>
                <h4>No saved quizzes yet</h4>
                <p>Generate and save a quiz to see it here.</p>
            </div>`;
        return;
    }

    el.innerHTML = savedQuizzes.map((q, idx) => `
        <div class="saved-quiz-item" style="animation-delay:${idx * 0.05}s">
            <div class="saved-quiz-icon">❓</div>
            <div class="saved-quiz-info">
                <div class="saved-quiz-title">${Security.escape(q.activityLabel || q.topicLabel || 'Quiz')}</div>
                <div class="saved-quiz-meta">
                    ${Security.escape(q.topicLabel || '')} · 
                    ${Security.escape(q.grade || '')} · ${Security.escape(q.difficulty || '')} · ${Security.escape(q.createdAt || '')}
                    · ${(q.pretest || []).length} pre-test · ${(q.posttest || []).length} post-test
                </div>
            </div>
            <div class="saved-quiz-actions" style="display:flex;gap:6px;flex-shrink:0">
                <button class="tbl-btn view"     onclick="viewSavedQuiz(${idx})">View</button>
                <button class="tbl-btn edit"     onclick="editSavedQuiz(${idx})">Edit</button>
                <button class="tbl-btn feedback" onclick="deleteSavedQuiz(${idx})">Delete</button>
            </div>
        </div>`).join('');
}

/* ------------------------------------------------------------------
   viewSavedQuiz
------------------------------------------------------------------ */
function viewSavedQuiz(idx) {
    const q = savedQuizzes[idx];
    if (!q) return;

    document.getElementById('view-quiz-title').textContent = q.activityLabel || q.topicLabel || 'Quiz';

    const renderModalQs = (questions) => {
        if (!Array.isArray(questions) || !questions.length) return '<p style="color:var(--text-3);font-size:12px">No questions.</p>';
        return questions.map((item, i) => {
            const opts = item.options || {};
            const ans  = (item.answer || '').toUpperCase();
            return `
            <div class="modal-q-item">
                <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:6px">
                    <span class="modal-q-num">${i + 1}</span>
                    <div class="modal-q-text">${Security.escape(item.question)}</div>
                </div>
                <div class="modal-q-opts">
                    ${Object.entries(opts).map(([l, t]) =>
                        `<div class="modal-q-opt${l.toUpperCase() === ans ? ' correct' : ''}">
                            ${l}. ${Security.escape(t)}${l.toUpperCase() === ans ? ' ✓' : ''}
                        </div>`
                    ).join('')}
                </div>
            </div>`;
        }).join('');
    };

    const renderActivityModal = (acts) => {
        if (!Array.isArray(acts) || !acts.length) return '<p style="color:var(--text-3);font-size:12px">No activity items.</p>';
        return acts.map((a, i) => `
            <div class="modal-q-item">
                <div style="display:flex;align-items:flex-start;gap:8px">
                    <span class="modal-q-num" style="background:var(--purple)">${i + 1}</span>
                    <div>
                        <div class="modal-q-text">${Security.escape(a.question)}</div>
                        ${a.instruction ? `<div style="font-size:12px;color:var(--text-3);margin-top:4px">📝 ${Security.escape(a.instruction)}</div>` : ''}
                    </div>
                </div>
            </div>`).join('');
    };

    document.getElementById('view-quiz-body').innerHTML = `
        <div style="margin-bottom:16px;padding:12px;background:var(--bg);border-radius:10px;font-size:12px;color:var(--text-3)">
            <b style="color:var(--text-2)">${Security.escape(q.topicLabel || '')}</b> ·
            ${Security.escape(q.grade || '')} · ${Security.escape(q.difficulty || '')} · ${Security.escape(q.createdAt || '')}
        </div>
        <div style="font-size:14px;font-weight:800;color:var(--blue);margin-bottom:10px">📋 Pre-Test (${(q.pretest || []).length} items)</div>
        ${renderModalQs(q.pretest)}
        <div style="font-size:14px;font-weight:800;color:var(--purple);margin:16px 0 10px">⚡ Activity (${(q.activity || []).length} items)</div>
        ${renderActivityModal(q.activity)}
        <div style="font-size:14px;font-weight:800;color:var(--green);margin:16px 0 10px">✅ Post-Test (${(q.posttest || []).length} items)</div>
        ${renderModalQs(q.posttest)}
    `;

    openModal('modal-view-quiz');
}

/* ------------------------------------------------------------------
   editSavedQuiz — manual edit + re-generate option
------------------------------------------------------------------ */
async function editSavedQuiz(idx) {
    const q = savedQuizzes[idx];
    if (!q) return;

    const { isConfirmed, isDenied } = await Swal.fire({
        title: 'Edit Quiz',
        html: `
            <div style="text-align:left;font-family:'Plus Jakarta Sans',sans-serif">
                <p style="font-size:13px;color:#6b7280;margin-bottom:16px">
                    <strong style="color:#111827">${Security.escape(q.activityLabel || q.topicLabel || 'Quiz')}</strong><br>
                    ${Security.escape(q.grade || '')} · ${Security.escape(q.difficulty || '')}
                </p>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="padding:14px;border-radius:10px;border:1.5px solid #bfdbfe;background:#eff6ff">
                        <div style="font-size:13px;font-weight:700;color:#1e40af;margin-bottom:4px">✏️ Manual Edit</div>
                        <div style="font-size:12px;color:#3b82f6">Edit questions, options, and answers directly</div>
                    </div>
                    <div style="padding:14px;border-radius:10px;border:1.5px solid #bbf7d0;background:#f0fdf4">
                        <div style="font-size:13px;font-weight:700;color:#065f46;margin-bottom:4px">🤖 Re-generate with AI</div>
                        <div style="font-size:12px;color:#10b981">Generate brand new questions for this topic</div>
                    </div>
                </div>
            </div>`,
        showCancelButton:   true,
        showDenyButton:     true,
        confirmButtonColor: '#2563eb',
        denyButtonColor:    '#10b981',
        cancelButtonColor:  '#6b7280',
        confirmButtonText:  '✏️ Manual Edit',
        denyButtonText:     '🤖 Re-generate',
        cancelButtonText:   'Cancel',
    });

    if (isConfirmed) {
        openEditQuizModal(idx);
    } else if (isDenied) {
        await reGenerateQuiz(idx);
    }
}

/* ------------------------------------------------------------------
   openEditQuizModal — open modal with editable questions
------------------------------------------------------------------ */
function openEditQuizModal(idx) {
    const q = savedQuizzes[idx];
    if (!q) return;

    currentQuiz = {
        ...q,
        id:            q.id || Date.now(),
        topicKey:      q.topicKey || '',
        topicLabel:    q.topicLabel || '',
        activityLabel: q.activityLabel || q.topicLabel || '',
        grade:         q.grade || 'Grade 10',
        difficulty:    q.difficulty || 'medium',
        createdAt:     q.createdAt || new Date().toISOString(),
        pretest:       q.pretest  || [],
        posttest:      q.posttest || [],
        activity:      q.activity || [],
        counts: {
            pre:  (q.pretest  || []).length,
            act:  (q.activity || []).length,
            post: (q.posttest || []).length,
        },
        _savedIdx: idx,
        _dbId:     q.id,
    };

    setQuizUIState('results');
    renderQuizResults(currentQuiz);
    navigate('quiz');
    toast('success', '✏️ Edit the questions below, then click "Save Quiz" to update!');
}

/* ------------------------------------------------------------------
   reGenerateQuiz — re-generate questions for a saved quiz topic
------------------------------------------------------------------ */
async function reGenerateQuiz(idx) {
    const q = savedQuizzes[idx];
    if (!q) return;

    const topicKeyMap = {
        'Module 1: Sequences and Series': 'sequences',
        'Module 2: Polynomials':          'polynomials',
        'Module 3: Advanced Equations':   'advanced',
    };

    const topicKey = topicKeyMap[q.topicLabel] || 'sequences';

    const topicSel = document.getElementById('quiz-topic');
    const actSel   = document.getElementById('quiz-activity');
    const diffSel  = document.getElementById('quiz-difficulty');

    if (topicSel) topicSel.value = topicKey;
    updateActivityOptions();

    const activityOptions = ACTIVITY_OPTIONS[topicKey] || [];
    const matchedAct = activityOptions.find(o =>
        o.label === q.activityLabel || o.value === q.topicKey
    );
    if (actSel && matchedAct) actSel.value = matchedAct.value;
    if (diffSel) diffSel.value = q.difficulty || 'medium';

    navigate('quiz');

    await Swal.fire({
        title:             '🤖 Re-generate Quiz?',
        html:              `<p style="font-size:13px;color:#6b7280;font-family:'Plus Jakarta Sans',sans-serif">
                               This will generate new questions for<br>
                               <strong style="color:#111827">${Security.escape(q.activityLabel || q.topicLabel)}</strong><br><br>
                               The old quiz will be replaced after saving.
                           </p>`,
        icon:              'question',
        showCancelButton:  true,
        confirmButtonColor:'#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '🤖 Generate Now',
        cancelButtonText:  'Cancel',
    }).then(async r => {
        if (r.isConfirmed) {
            window._regenIdx = idx;
            await generateQuiz();
        }
    });
}

/* ------------------------------------------------------------------
   deleteSavedQuiz — delete from quizzes + quiz_published tables
------------------------------------------------------------------ */
async function deleteSavedQuiz(idx) {
    const q = savedQuizzes[idx];
    if (!q) return;

    const conf = await Swal.fire({
        title:             'Delete Quiz?',
        html:              `<p style="font-size:13px;color:#6b7280;font-family:'Plus Jakarta Sans',sans-serif">
                               <strong style="color:#111827">${Security.escape(q.activityLabel || q.topicLabel || 'Quiz')}</strong>
                               will be permanently deleted.<br><br>
                               <span style="color:#ef4444;font-weight:700">⚠️ This will also unpublish it from students — they will see a "No Quiz Available" message.</span>
                           </p>`,
        icon:              'warning',
        showCancelButton:  true,
        confirmButtonColor:'#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Delete',
        cancelButtonText:  'Cancel',
    });

    if (!conf.isConfirmed) return;

    try {
        // 1. Delete from quizzes table
        if (q.id) {
            await sbDelete('quizzes', q.id);
        }

        // 2. Find the topic key from activity label
        // Map activity label → topic key
        const actLabelToTopicKey = {};
        Object.values(ACTIVITY_OPTIONS).flat().forEach(o => {
            const k = activityValueToTopicKey(o.value);
            if (k) actLabelToTopicKey[o.label] = k;
        });

        const topicKey = q.topicKey 
            || actLabelToTopicKey[q.activityLabel] 
            || actLabelToTopicKey[q.topicLabel]
            || null;

        // 3. Unpublish from quiz_published if found
        if (topicKey) {
            try {
                await unpublishQuiz(topicKey);
                console.log(`✓ Unpublished topic: ${topicKey}`);
            } catch (e) {
                console.warn('Could not unpublish (may not exist):', e.message);
            }
        } else {
            console.warn('Could not find topicKey to unpublish for:', q.activityLabel);
        }

        // 4. Remove from local array
        savedQuizzes.splice(idx, 1);
        renderSavedQuizzes();
        updateQuizMetrics();
        await renderPublishedPanel();

        toast('success', 'Quiz deleted — students will see "No Quiz Available" for this topic. ✅');

    } catch (err) {
        console.error('Delete quiz error:', err);
        warn('Delete Failed', err.message);
    }
}

/* ============================================================
   QUIZ PUBLISHING & STUDENT DELIVERY
   ============================================================ */

/**
 * Publishes the current quiz to students via quiz_published table
 */
async function publishCurrentQuiz() {
    if (!currentQuiz) return warn('No quiz', 'Generate a quiz first.');

    const actVal = document.getElementById('quiz-activity')?.value;
    if (!actVal)  return warn('No activity selected', 'Please select an activity.');

    const topicKey = activityValueToTopicKey(actVal);
    if (!topicKey) return warn('Unknown topic key', 'Cannot map activity to a topic key.');

    const topicLabel = (ACTIVITY_OPTIONS[document.getElementById('quiz-topic')?.value] || [])
        .find(o => o.value === actVal)?.label || actVal;

    const conf = await Swal.fire({
        title:             'Publish quiz to students?',
        html:              `<p style="font-size:13px;color:#6b7280;font-family:'Plus Jakarta Sans',sans-serif">
                               Students will see your generated questions for
                               <strong style="color:#111827">${Security.escape(topicLabel)}</strong>
                               instead of the default questions.<br><br>
                               Pre-test: <b>${currentQuiz.pretest.length}</b> items &nbsp;·&nbsp;
                               Activity: <b>${currentQuiz.activity.length}</b> items &nbsp;·&nbsp;
                               Post-test: <b>${currentQuiz.posttest.length}</b> items
                           </p>`,
        icon:              'question',
        showCancelButton:  true,
        confirmButtonColor:'#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '📤 Publish',
        cancelButtonText:  'Cancel',
    });

    if (!conf.isConfirmed) return;

    try {
        // Check if already published for this topic
        const existing = await sbSelect('quiz_published', `?topic_key=eq.${encodeURIComponent(topicKey)}`);
        
        if (existing && existing.length > 0) {
            const { isConfirmed } = await Swal.fire({
                title:             'Quiz Already Published',
                html:              `<p style="font-size:13px;color:#6b7280;font-family:'Plus Jakarta Sans',sans-serif">
                                       A quiz for <strong style="color:#111827">${Security.escape(topicLabel)}</strong> 
                                       is already published to students.<br><br>
                                       Do you want to <strong style="color:#2563eb">replace</strong> it with this new one?<br>
                                       <span style="font-size:11px;color:#9ca3af">The old quiz will be overwritten.</span>
                                   </p>`,
                icon:              'warning',
                showCancelButton:  true,
                confirmButtonColor:'#2563eb',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '🔄 Yes, Replace',
                cancelButtonText:  'Cancel',
            });

            if (!isConfirmed) return;
        }

        await publishQuizToStudents(topicKey, currentQuiz);
        logActivity('Quiz Published', `"${topicLabel}" published to students`, 'quiz');
        toast('success', '✅ Quiz published! Students will see your questions.');

        await renderPublishedPanel();
    } catch (err) {
        warn('Publish Failed', err.message);
    }
}

/**
 * Writes pre/post/activity to quiz_published keyed by topic_key
 */
async function publishQuizToStudents(topicKey, quiz) {
    if (!quiz) return;

    const payload = {
        topic_key:   topicKey,
        pretest:     JSON.stringify(quiz.pretest  || []),
        posttest:    JSON.stringify(quiz.posttest || []),
        activity:    JSON.stringify(quiz.activity || []),
        published_at: new Date().toISOString(),
    };

    const res = await fetch(`${SUPABASE_URL}/rest/v1/quiz_published?on_conflict=topic_key`, {
        method:  'POST',
        headers: {
            'apikey':        SUPABASE_ANON_KEY,
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            'Content-Type':  'application/json',
            'Prefer':        'resolution=merge-duplicates,return=representation',
        },
        body: JSON.stringify([payload]),
    });

    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `Publish failed (${res.status})`);
    }
    return res.json();
}

/**
 * Loads all published quizzes for display
 */
async function loadPublishedQuizzes() {
    try {
        return await sbSelect('quiz_published', '?select=*');
    } catch (e) {
        console.warn('Could not load published quizzes:', e.message);
        return [];
    }
}

/**
 * Unpublish quiz - removes from students' view
 */
async function unpublishQuiz(topicKey) {
    const res = await fetch(
        `${SUPABASE_URL}/rest/v1/quiz_published?topic_key=eq.${encodeURIComponent(topicKey)}`,
        {
            method:  'DELETE',
            headers: {
                'apikey':        SUPABASE_ANON_KEY,
                'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            },
        }
    );
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `Unpublish failed (${res.status})`);
    }
    
    // Clear from student browser cache if it exists
    if (window._publishedTopicKeys && typeof window._publishedTopicKeys.delete === 'function') {
        window._publishedTopicKeys.delete(topicKey);
        console.log(`🗑️ Cleared ${topicKey} from _publishedTopicKeys cache`);
    }
    
    return true;
}

/**
 * Renders the published quizzes panel
 */
async function renderPublishedPanel() {
    const el = document.getElementById('quiz-published-list');
    if (!el) return;

    el.innerHTML = `<div style="color:var(--text-3);font-size:12px;padding:8px 0">Loading…</div>`;

    const rows = await loadPublishedQuizzes();

    const keyToLabel = {};
    Object.values(ACTIVITY_OPTIONS).flat().forEach(o => {
        const k = activityValueToTopicKey(o.value);
        if (k) keyToLabel[k] = o.label;
    });

    if (!rows.length) {
        el.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h4>No published quizzes</h4>
                <p>Generate a quiz and click "Publish to students" to send it live.</p>
            </div>`;
        return;
    }

    el.innerHTML = rows.map(r => {
        const label   = keyToLabel[r.topic_key] || r.topic_key;
        const preLen  = safeParseJSON(r.pretest,  []).length;
        const postLen = safeParseJSON(r.posttest, []).length;
        const actLen  = safeParseJSON(r.activity, []).length;
        const date    = formatDate(r.published_at);
        return `
        <div class="saved-quiz-item" style="border-left:3px solid #2563eb">
            <div class="saved-quiz-icon" style="background:#eff6ff;color:#2563eb">📤</div>
            <div class="saved-quiz-info">
                <div class="saved-quiz-title">${Security.escape(label)}</div>
                <div class="saved-quiz-meta">
                    Published ${Security.escape(date)} ·
                    ${preLen} pre · ${actLen} act · ${postLen} post
                </div>
            </div>
            <div class="saved-quiz-actions">
                <button class="tbl-btn feedback"
                        onclick="confirmUnpublish('${Security.escape(r.topic_key)}', '${Security.escape(label)}')">
                    Unpublish
                </button>
            </div>
        </div>`;
    }).join('');
}

/**
 * Confirm unpublish action
 */
async function confirmUnpublish(topicKey, label) {
    const conf = await Swal.fire({
        title:             'Unpublish this quiz?',
        text:              `Students will go back to seeing the default questions for "${label}".`,
        icon:              'warning',
        showCancelButton:  true,
        confirmButtonColor:'#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Unpublish',
        cancelButtonText:  'Cancel',
    });
    if (!conf.isConfirmed) return;
    try {
        await unpublishQuiz(topicKey);
        toast('success', 'Quiz unpublished.');
        await renderPublishedPanel();
    } catch (err) {
        warn('Failed', err.message);
    }
}

/**
 * Load custom topics from Supabase
 */
async function loadCustomTopics() {
    try {
        return await sbSelect('quiz_custom_topics', '?select=*&order=created_at.asc');
    } catch (e) {
        console.warn('Could not load custom topics:', e.message);
        return [];
    }
}

/**
 * Add a custom topic
 */
async function addCustomTopic(moduleKey, topicKey, topicName) {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/quiz_custom_topics`, {
        method:  'POST',
        headers: {
            'apikey':        SUPABASE_ANON_KEY,
            'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
            'Content-Type':  'application/json',
            'Prefer':        'return=representation',
        },
        body: JSON.stringify([{ module_key: moduleKey, topic_key: topicKey, topic_name: topicName }]),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `Add topic failed (${res.status})`);
    }
    return res.json();
}

/**
 * Delete a custom topic
 */
async function deleteCustomTopic(id) {
    return sbDelete('quiz_custom_topics', id);
}

/**
 * Generate topic key from name
 */
function generateTopicKey(name) {
    const slug  = name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    const short = slug.substring(0, 20);
    return 'custom_' + short + '_' + Date.now().toString().slice(-4);
}

/**
 * Render topic manager panel
 */
async function renderTopicManager() {
    const el = document.getElementById('quiz-topic-manager');
    if (!el) return;

    const customTopics = await loadCustomTopics();

    const builtinGroups = [
        {
            label: 'Module 1: Sequences and Series',
            key:   'sequences',
            topics: ACTIVITY_OPTIONS.sequences,
        },
        {
            label: 'Module 2: Polynomials',
            key:   'polynomials',
            topics: ACTIVITY_OPTIONS.polynomials,
        },
        {
            label: 'Module 3: Advanced Equations',
            key:   'advanced',
            topics: ACTIVITY_OPTIONS.advanced,
        },
    ];

    const builtinHTML = builtinGroups.map(g => `
        <div style="margin-bottom:16px">
            <div style="font-size:11px;font-weight:800;color:#6b7280;text-transform:uppercase;
                        letter-spacing:.08em;margin-bottom:8px">${Security.escape(g.label)}</div>
            ${g.topics.map(t => `
            <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;
                        border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;
                        margin-bottom:5px">
                <span style="font-size:12px;font-weight:600;color:#374151;flex:1">
                    ${Security.escape(t.label)}
                </span>
                <span style="font-size:10px;font-weight:700;color:#9ca3af;background:#f3f4f6;
                              padding:2px 8px;border-radius:4px">Built-in</span>
            </div>`).join('')}
        </div>`).join('');

    const customHTML = customTopics.length ? `
        <div style="margin-bottom:16px">
            <div style="font-size:11px;font-weight:800;color:#6b7280;text-transform:uppercase;
                        letter-spacing:.08em;margin-bottom:8px">Custom Topics</div>
            ${customTopics.map(t => `
            <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;
                        border-radius:8px;background:#faf5ff;border:1px solid #e9d5ff;
                        margin-bottom:5px">
                <span style="font-size:12px;font-weight:600;color:#374151;flex:1">
                    ${Security.escape(t.topic_name)}
                    <span style="font-size:10px;color:#9ca3af;font-weight:500;margin-left:6px">
                        ${Security.escape(t.module_key)}
                    </span>
                </span>
                <button class="tbl-btn feedback" style="padding:4px 10px;font-size:11px"
                        onclick="removeCustomTopic('${Security.escape(t.id)}', '${Security.escape(t.topic_name)}')">
                    Remove
                </button>
            </div>`).join('')}
        </div>` : '';

    el.innerHTML = `
        <div style="font-size:14px;font-weight:800;color:var(--text);margin-bottom:14px">
            Topic Manager
        </div>
        ${builtinHTML}
        ${customHTML}
        <button class="primary-btn" style="width:100%;margin-top:8px;font-size:13px;padding:10px"
                onclick="openAddTopicDialog()">
            + Add Custom Topic
        </button>`;
}

/**
 * Open dialog to add custom topic
 */
async function openAddTopicDialog() {
    const { value: formValues } = await Swal.fire({
        title: 'Add Custom Topic',
        html: `
            <div style="text-align:left;font-family:'Plus Jakarta Sans',sans-serif">
                <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;
                              letter-spacing:.06em;display:block;margin-bottom:4px">
                    Topic Name
                </label>
                <input id="ct-name" class="swal2-input" placeholder="e.g., Systems of Equations"
                       style="margin:0 0 12px;font-size:13px" maxlength="80">

                <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;
                              letter-spacing:.06em;display:block;margin-bottom:4px">
                    Module Group
                </label>
                <select id="ct-module" class="swal2-select"
                        style="margin:0;width:100%;padding:10px 12px;border-radius:9px;
                               border:1.5px solid #e5e7eb;font-family:inherit;font-size:13px">
                    <option value="sequences">Module 1: Sequences and Series</option>
                    <option value="polynomials">Module 2: Polynomials</option>
                    <option value="advanced">Module 3: Advanced Equations</option>
                    <option value="custom">Other / Custom Module</option>
                </select>
            </div>`,
        showCancelButton:  true,
        confirmButtonColor:'#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Add Topic',
        cancelButtonText:  'Cancel',
        didOpen: () => document.getElementById('ct-name').focus(),
        preConfirm: () => ({
            name:   Security.sanitize(document.getElementById('ct-name').value.trim()),
            module: document.getElementById('ct-module').value,
        }),
    });

    if (!formValues) return;
    const { name, module: moduleKey } = formValues;

    if (!name || name.length < 3) {
        return Swal.fire({ icon: 'error', title: 'Name required', text: 'Enter at least 3 characters.', confirmButtonColor: '#2563eb' });
    }

    try {
        const topicKey = generateTopicKey(name);
        await addCustomTopic(moduleKey, topicKey, name);

        const newOption = { value: topicKey, label: name };
        if (!ACTIVITY_OPTIONS[moduleKey]) ACTIVITY_OPTIONS[moduleKey] = [];
        ACTIVITY_OPTIONS[moduleKey].push(newOption);

        updateActivityOptions();

        logActivity('Topic Added', `"${name}" added to ${moduleKey}`, 'quiz');
        toast('success', `"${Security.escape(name)}" topic added!`);
        await renderTopicManager();
    } catch (err) {
        warn('Failed', err.message);
    }
}

/**
 * Remove custom topic
 */
async function removeCustomTopic(id, name) {
    const conf = await Swal.fire({
        title:             `Remove "${Security.escape(name)}"?`,
        text:              'This will remove the topic from the quiz generator.',
        icon:              'warning',
        showCancelButton:  true,
        confirmButtonColor:'#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Remove',
        cancelButtonText:  'Cancel',
    });
    if (!conf.isConfirmed) return;
    try {
        await deleteCustomTopic(id);
        toast('success', 'Topic removed.');
        await renderTopicManager();
        updateActivityOptions();
    } catch (err) {
        warn('Failed', err.message);
    }
}

/* ------------------------------------------------------------------
   initQuizPage — called when navigating to the quiz page
------------------------------------------------------------------ */
async function initQuizPage() {
    updateActivityOptions();

    // Load custom topics from Supabase and inject into ACTIVITY_OPTIONS
    const customTopics = await loadCustomTopics();
    customTopics.forEach(t => {
        const key = t.module_key || 'custom';
        if (!ACTIVITY_OPTIONS[key]) ACTIVITY_OPTIONS[key] = [];
        if (!ACTIVITY_OPTIONS[key].find(o => o.value === t.topic_key)) {
            ACTIVITY_OPTIONS[key].push({ value: t.topic_key, label: t.topic_name });
        }
    });
    updateActivityOptions();

    await loadSavedQuizzes();
    await renderPublishedPanel();
    await renderTopicManager();
}

/* ============================================================
   GLOBAL EXPOSE
   ============================================================ */
Object.assign(window, {
    // Navigation
    navigate,

    // Students
    filterStudents, viewStudent, openFeedback, saveFeedback, viewStudentAnswers,

    // Modules
    filterModules, openAddModule, saveModule, viewModule, editModule, deleteModule, sendToDownloads,
    loadAndRenderModules, cancelModule, clearFile, resetModuleForm,

    // Reports / Sections
    openAddSection, editSection, deleteSection, showReportExportPicker,

    // Profile
    updatePassword, clearPasswordForm,

    // Modals
    openModal, closeModal,

    // Auth
    confirmLogout,

    // Quiz Generator
    updateActivityOptions,
    switchQuizTab,
    generateQuiz,
    saveQuizToSupabase,
    viewSavedQuiz,
    initQuizPage,
    getQuizCounts,
    quizSaveEdit,
    quizMarkAnswer,
    insertQuizSymbol,
    quizSymbolSwitchTab,
    quizSymbolSetSearch,
    handleQuizSymbolInsert,
    injectQuizEditStyles,
    publishCurrentQuiz,
    publishQuizToStudents,
    loadPublishedQuizzes,
    unpublishQuiz,
    renderPublishedPanel,
    confirmUnpublish,
    loadCustomTopics,
    addCustomTopic,
    deleteCustomTopic,
    generateTopicKey,
    renderTopicManager,
    openAddTopicDialog,
    removeCustomTopic,
    editSavedQuiz,
    openEditQuizModal,
    reGenerateQuiz,
    deleteSavedQuiz,
});

/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', async () => {
    injectQuizEditStyles();
    renderSymbolPalette();
    
    document.querySelectorAll('[data-page]').forEach(btn => {
        btn.addEventListener('click', () => navigate(btn.dataset.page));
    });

    ['logout-btn-mobile', 'logout-btn-desktop'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', confirmLogout);
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(overlay.id); });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape')
            document.querySelectorAll('.modal-overlay.open').forEach(o => closeModal(o.id));
    });

    initFileUpload();

    // Initial modules load
    await loadAndRenderModules();

    // Load sections from database
    await loadSections();

    // Load real students + progress from the server
    await loadStudents();

    // Load real feedback history
    await loadFeedbacks();

    renderHome();
    renderStudents();

    // Keep the roster (Students tab) and the stat tiles + recent-student
    // preview (Home tab) live without a manual reload: every 20s, check for
    // changed progress/approvals/feedback — but only while one of those two
    // tabs is actually open, so this never fetches or re-renders in the
    // background on other tabs. Pauses automatically while the browser tab
    // is hidden (see resources/js/polling.js).
    startPolling(async () => {
        const onStudents = document.getElementById('page-students')?.classList.contains('active');
        const onHome = document.getElementById('page-home')?.classList.contains('active');
        if (onStudents) {
            await loadStudents();
            renderStudents();
        } else if (onHome) {
            await Promise.all([loadStudents(), loadFeedbacks()]);
            renderHome();
        }
    }, 20000);

    // Modules tab: see an admin's approve/reject decision land live, without
    // a manual reload — see pollModulesTick() above for why this can't use
    // the ETag/304 pattern the other pages use.
    startPolling(() => {
        if (!document.getElementById('page-modules')?.classList.contains('active')) return;
        return pollModulesTick();
    }, 30000);
});
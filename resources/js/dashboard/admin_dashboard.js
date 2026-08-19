/**
 * admin_dashboard.js
 * Path: resources/js/dashboard/admin_dashboard.js
 *
 * Users are managed through the Laravel-backed /admin/users endpoints
 * (real `users` table). Everything else persists straight to Supabase:
 *   - module_status     → Content moderation queue
 *   - activity_logs     → Event logs
 *   - platform_settings → Settings key/value store
 *   - student_progress  → Platform analytics (read-only here)
 */

import Swal from 'sweetalert2';

'use strict';

/* ============================================================
   SUPABASE CONFIG  (reads from window.__ENV__ set in Blade)
   ============================================================ */
const SUPABASE_URL      = window.__ENV__?.SUPABASE_URL;
const SUPABASE_ANON_KEY = window.__ENV__?.SUPABASE_ANON_KEY;
const BUCKET_NAME       = 'modules';
const STATUS_TABLE      = 'module_status';
const ACTIVITY_TABLE    = 'activity_logs';
const SETTINGS_TABLE    = 'platform_settings';

/* ============================================================
   SUPABASE REST HELPERS
   ============================================================ */
const sbHeaders = (extra = {}) => ({
    'apikey':        SUPABASE_ANON_KEY,
    'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
    'Content-Type':  'application/json',
    ...extra,
});

async function sbSelect(table, params = '') {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}${params}`, {
        headers: sbHeaders(),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB read failed (${res.status})`);
    }
    return res.json();
}

async function sbInsert(table, data) {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}`, {
        method: 'POST',
        headers: sbHeaders({ 'Prefer': 'return=representation' }),
        body: JSON.stringify(data),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB insert failed (${res.status})`);
    }
    return res.json();
}

async function sbUpsert(table, data) {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}`, {
        method: 'POST',
        headers: sbHeaders({ 'Prefer': 'resolution=merge-duplicates,return=representation' }),
        body: JSON.stringify(data),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB upsert failed (${res.status})`);
    }
    return res.json();
}

async function sbUpdate(table, match, data) {
    const query = Object.entries(match)
        .map(([k, v]) => `${k}=eq.${encodeURIComponent(v)}`)
        .join('&');
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}?${query}`, {
        method: 'PATCH',
        headers: sbHeaders({ 'Prefer': 'return=representation' }),
        body: JSON.stringify(data),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB update failed (${res.status})`);
    }
    return res.json();
}

async function sbDelete(table, match) {
    const query = Object.entries(match)
        .map(([k, v]) => `${k}=eq.${encodeURIComponent(v)}`)
        .join('&');
    const res = await fetch(`${SUPABASE_URL}/rest/v1/${table}?${query}`, {
        method: 'DELETE',
        headers: sbHeaders(),
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `DB delete failed (${res.status})`);
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
    isValidEmail(e)  { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(e); },
    isValidRole(r)   { return ['admin','teacher','student'].includes(r); },
    isValidStatus(s) { return ['Active','Inactive'].includes(s); },
};

/* ============================================================
   STATE
   ============================================================ */
let users    = [];
let contents = [];
let activity = [];

let userEditId     = null;
let contentLoading = false;
const USERS_PER_PAGE = 6;
let userPage = 1;

/* ============================================================
   INIT — load all data from Supabase on startup
   ============================================================ */
async function initDashboard() {
    try {
        await Promise.all([
            loadUsers(),
            loadActivity(),
            loadSettings(),
        ]);
    } catch (err) {
        console.error('Init error:', err);
    }
    renderHome();
    renderUsers();
}

/* ============================================================
   USERS — Supabase CRUD
   ============================================================ */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content;

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken() ?? '',
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...options.headers,
        },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || `Request failed (${res.status})`);
    return data;
}

/** Fetch all real platform users (Laravel-backed, not the disconnected admin_users table) */
async function loadUsers() {
    try {
        const data = await apiFetch('/admin/users');
        users = Array.isArray(data.users) ? data.users : [];
    } catch (err) {
        console.warn('loadUsers error:', err.message);
        users = [];
    }
}

function getFilteredUsers() {
    const q    = Security.sanitize(document.getElementById('user-search')?.value || '').toLowerCase();
    const role = document.getElementById('user-role-filter')?.value || '';
    return users.filter(u =>
        (u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)) &&
        (!role || u.role === role)
    );
}

function renderUsers() {
    setText('m-total',    users.length);
    setText('m-students', users.filter(u => u.role === 'student').length);
    setText('m-teachers', users.filter(u => u.role === 'teacher').length);
    setText('u-total',    users.length);
    setText('u-students', users.filter(u => u.role === 'student').length);
    setText('u-teachers', users.filter(u => u.role === 'teacher').length);

    const filtered   = getFilteredUsers();
    const totalPages = Math.max(1, Math.ceil(filtered.length / USERS_PER_PAGE));
    if (userPage > totalPages) userPage = totalPages;
    const slice = filtered.slice((userPage - 1) * USERS_PER_PAGE, userPage * USERS_PER_PAGE);

    const tbody = document.getElementById('users-tbody');
    if (!slice.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><div class="empty-icon">👤</div><h4>No users found</h4><p>Try a different search or filter.</p></div></td></tr>`;
    } else {
        tbody.innerHTML = slice.map((u, i) => `
            <tr>
                <td style="color:var(--text-4);font-size:12px">${(userPage - 1) * USERS_PER_PAGE + i + 1}</td>
                <td><b>${Security.escape(u.name)}</b></td>
                <td style="color:var(--text-3)">${Security.escape(u.email)}</td>
                <td><span class="role-badge role-${Security.escape(u.role)}">${Security.escape(capitalize(u.role))}</span></td>
                <td style="font-size:12px;color:var(--text-3)">${Security.escape(u.joined)}</td>
                <td><span class="status-badge ${u.status === 'Active' ? 'badge-good' : 'badge-danger'}">${Security.escape(u.status)}</span></td>
                <td>
                    <button class="tbl-btn edit" onclick="editUser('${Security.escape(u.id)}')">Edit</button>
                    <button class="tbl-btn del"  onclick="deleteUser('${Security.escape(u.id)}')">Delete</button>
                </td>
            </tr>`).join('');
    }

    const pg = document.getElementById('user-pagination');
    pg.innerHTML = '';
    pg.appendChild(makePgBtn('‹ Prev', userPage === 1, () => { userPage--; renderUsers(); }));
    for (let i = 1; i <= totalPages; i++) {
        const btn = makePgBtn(i, false, () => { userPage = i; renderUsers(); });
        if (i === userPage) btn.classList.add('active');
        pg.appendChild(btn);
    }
    pg.appendChild(makePgBtn('Next ›', userPage === totalPages, () => { userPage++; renderUsers(); }));
}

function filterUsers() { userPage = 1; renderUsers(); }

function editUser(id) {
    const u = users.find(x => x.id === id);
    if (!u) return;
    userEditId = id;
    document.getElementById('modal-user-title').textContent = 'Edit User';
    document.getElementById('u-name').value   = u.name;
    document.getElementById('u-email').value  = u.email;
    document.getElementById('u-role').value   = u.role;
    document.getElementById('u-status').value = u.status === 'Active' ? 'Active' : 'Inactive';
    openModal('modal-user');
}

async function saveUser() {
    if (!userEditId) return; // accounts are created via self-service registration + approval, not here

    const name   = Security.sanitize(document.getElementById('u-name').value);
    const email  = Security.sanitize(document.getElementById('u-email').value);
    const role   = document.getElementById('u-role').value;
    const status = document.getElementById('u-status').value;

    if (!name || name.length < 2)        return warn('Missing field', 'Please enter a valid full name.');
    if (!Security.isValidEmail(email))    return warn('Invalid email', 'Please enter a valid email address.');
    if (!Security.isValidRole(role))      return warn('Invalid role', 'Please select a valid role.');
    if (!Security.isValidStatus(status))  return warn('Invalid status', 'Please select a valid status.');

    const saveBtn = document.querySelector('#modal-user .btn-save');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }

    try {
        const data = await apiFetch(`/admin/users/${userEditId}`, {
            method: 'PATCH',
            body: JSON.stringify({ name, email, role, status }),
        });
        const u = users.find(x => x.id === userEditId);
        if (u) Object.assign(u, data.user);
        await logEvent('content', 'User Updated', `${name}'s profile was edited`, 'Updated');
        toast('success', 'User updated successfully.');

        closeModal('modal-user');
        renderUsers();
        renderHome();
    } catch (err) {
        warn('Save Failed', err.message || 'Could not save user. Please try again.');
    } finally {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save User'; }
    }
}

async function deleteUser(id) {
    const u = users.find(x => x.id === id);
    if (!u) return;
    Swal.fire({
        title: 'Delete User?',
        html: `Remove <strong>${Security.escape(u.name)}</strong>? This cannot be undone.`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Yes, delete',
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            await apiFetch(`/admin/users/${id}`, { method: 'DELETE' });
            await logEvent('system', 'User Deleted', `Account for ${u.name} removed`, 'System');
            users = users.filter(x => x.id !== id);
            renderUsers();
            renderHome();
            toast('success', 'User deleted.');
        } catch (err) {
            warn('Delete Failed', err.message || 'Could not delete user.');
        }
    });
}

/* ============================================================
   ACTIVITY LOGS — Supabase
   ============================================================ */

/** Load last 50 activity logs from Supabase */
async function loadActivity() {
    try {
        const rows = await sbSelect(ACTIVITY_TABLE, '?select=*&order=created_at.desc&limit=50');
        activity = rows.map(r => ({
            id:    r.id,
            type:  r.type,
            title: r.title,
            sub:   r.sub   || '',
            badge: r.badge || 'Event',
            time:  timeAgo(r.created_at),
            ts:    new Date(r.created_at).getTime(),
        }));
    } catch (err) {
        console.warn('loadActivity error:', err.message);
    }
}

/** Insert a new activity log into Supabase and prepend to local array */
async function logEvent(type, title, sub, badge) {
    const entry = { type, title, sub, badge: badge || 'Event' };
    try {
        const inserted = await sbInsert(ACTIVITY_TABLE, entry);
        const row = Array.isArray(inserted) ? inserted[0] : inserted;
        activity.unshift({
            id:    row.id,
            type:  row.type,
            title: row.title,
            sub:   row.sub   || '',
            badge: row.badge || 'Event',
            time:  'just now',
            ts:    Date.now(),
        });
        if (activity.length > 50) activity.pop();
    } catch (err) {
        // Log locally even if Supabase fails
        console.warn('logEvent failed:', err.message);
        activity.unshift({ ...entry, time: 'just now', ts: Date.now() });
    }
}

function renderActivity() {
    const filter   = document.getElementById('activity-filter')?.value || '';
    const filtered = filter ? activity.filter(a => a.type === filter) : activity;

    const today = activity.filter(a => (Date.now() - a.ts) < 86400000);
    setText('ac-events', today.length);
    setText('ac-logins', today.filter(a => a.type === 'login').length);
    setText('ac-errors', today.filter(a => a.type === 'error').length);

    const tl = document.getElementById('activity-timeline');
    if (!filtered.length) {
        tl.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><h4>No events logged yet</h4><p>Activity will appear here as users interact with the platform.</p></div>';
        return;
    }
    const dotColor = { registration:'blue', login:'green', content:'green', system:'orange', error:'red' };
    tl.innerHTML = filtered.map(a => `
        <div class="tl-item">
            <div class="tl-dot ${dotColor[a.type] || 'blue'}"></div>
            <div class="tl-title">${Security.escape(a.title)}</div>
            <div class="tl-sub">${Security.escape(a.sub)}</div>
            <div class="tl-time">${Security.escape(a.time)}</div>
        </div>`).join('');
}

function filterActivity() { renderActivity(); }

/* ============================================================
   PLATFORM SETTINGS — Supabase key/value store
   ============================================================ */

/** Load all settings from Supabase and populate form fields */
async function loadSettings() {
    try {
        const rows = await sbSelect(SETTINGS_TABLE, '?select=*');
        const map  = {};
        rows.forEach(r => { map[r.key] = r.value; });

        // Text fields
        setField('s-platform-name', map['platform_name'] || '');
        setField('s-admin-email',   map['admin_email']   || '');
        setField('s-desc',          map['platform_desc'] || '');

        // Notification toggles
        setToggle('notif-registration', map['notif_registration'] === 'true');
        setToggle('notif-content',      map['notif_content']      === 'true');
        setToggle('notif-errors',       map['notif_errors']       === 'true');
        setToggle('notif-weekly',       map['notif_weekly']       === 'true');

        // Feature flag toggles
        setToggle('feat-ai-tutor',      map['feat_ai_tutor']      === 'true');
        setToggle('feat-leaderboard',   map['feat_leaderboard']   === 'true');
        setToggle('feat-maintenance',   map['feat_maintenance']   === 'true');
        setToggle('feat-registration',  map['feat_registration']  === 'true');

    } catch (err) {
        console.warn('loadSettings error:', err.message);
    }
}

/** Save a batch of key/value pairs to Supabase platform_settings */
async function saveSettingsToSupabase(pairs) {
    const rows = Object.entries(pairs).map(([key, value]) => ({ key, value: String(value) }));
    await sbUpsert(SETTINGS_TABLE, rows);
}

async function savePlatformInfo() {
    const name  = Security.sanitize(document.getElementById('s-platform-name').value);
    const email = Security.sanitize(document.getElementById('s-admin-email').value);
    const desc  = Security.sanitize(document.getElementById('s-desc').value);

    if (!name || name.length < 2)              return warn('Platform name required', 'Please enter a platform name.');
    if (email && !Security.isValidEmail(email)) return warn('Invalid email', 'Please enter a valid admin email.');

    try {
        await saveSettingsToSupabase({
            platform_name: name,
            admin_email:   email,
            platform_desc: desc,
        });
        await logEvent('system', 'Settings Updated', 'Platform info was saved', 'System');
        toast('success', 'Platform info saved!');
    } catch (err) {
        warn('Save Failed', err.message || 'Could not save settings.');
    }
}

async function saveSettings(label) {
    let pairs = {};

    if (label === 'Notification') {
        pairs = {
            notif_registration: getToggle('notif-registration'),
            notif_content:      getToggle('notif-content'),
            notif_errors:       getToggle('notif-errors'),
            notif_weekly:       getToggle('notif-weekly'),
        };
    } else if (label === 'Feature Flags') {
        pairs = {
            feat_ai_tutor:     getToggle('feat-ai-tutor'),
            feat_leaderboard:  getToggle('feat-leaderboard'),
            feat_maintenance:  getToggle('feat-maintenance'),
            feat_registration: getToggle('feat-registration'),
        };
    } else if (label === 'Roles & Permissions') {
        // Roles table is static in this version; just log the action
        pairs = { roles_last_saved: new Date().toISOString() };
    }

    try {
        if (Object.keys(pairs).length) await saveSettingsToSupabase(pairs);
        await logEvent('system', 'Settings Updated', `${label} preferences saved`, 'System');
        toast('success', `${Security.sanitize(label)} saved!`);
    } catch (err) {
        warn('Save Failed', err.message || 'Could not save settings.');
    }
}

async function confirmDanger(action, desc) {
    Swal.fire({
        title: Security.escape(action) + '?', text: desc,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Yes, confirm',
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            if (action === 'Clear All Logs') {
                // Delete all rows from activity_logs in Supabase
                // Supabase REST: DELETE without a filter deletes all when using neq trick
                await fetch(`${SUPABASE_URL}/rest/v1/${ACTIVITY_TABLE}?id=neq.00000000-0000-0000-0000-000000000000`, {
                    method: 'DELETE',
                    headers: sbHeaders(),
                });
                activity = [];
                renderActivity();
            }
            toast('success', Security.escape(action) + ' complete.');
        } catch (err) {
            warn('Action Failed', err.message || 'Could not complete this action.');
        }
    });
}

/* ============================================================
   CONTENT — Supabase Storage + module_status table
   ============================================================ */
async function fetchContentsFromSupabase() {
    const listRes = await fetch(`${SUPABASE_URL}/storage/v1/object/list/${BUCKET_NAME}`, {
        method: 'POST',
        headers: sbHeaders(),
        body: JSON.stringify({ prefix: '', limit: 200, offset: 0 }),
    });
    if (!listRes.ok) {
        const err = await listRes.json().catch(() => ({}));
        throw new Error(err.message || `Bucket list failed (${listRes.status})`);
    }
    const files = await listRes.json();

    let statusRows = [];
    try {
        statusRows = await sbSelect(STATUS_TABLE, '?select=*');
    } catch (e) {
        console.warn('Could not fetch status table:', e.message);
    }

    const statusMap = {};
    statusRows.forEach(r => { statusMap[r.file_name] = r; });

    const merged = files
        .filter(f => f.name && !f.name.startsWith('.'))
        .map((f, idx) => {
            const publicUrl   = `${SUPABASE_URL}/storage/v1/object/public/${BUCKET_NAME}/${encodeURIComponent(f.name)}`;
            const rawName     = f.name.replace(/^\d+_/, '');
            const existing    = statusMap[f.name];
            const fileSizeRaw = f.metadata?.size ?? 0;
            return {
                id:          f.id || `file_${idx}`,
                storageName: f.name,
                name:        existing?.module_title ?? rawName,
                fileUrl:     publicUrl,
                size:        formatSize(fileSizeRaw),
                rawSize:     fileSizeRaw,
                joined:      formatDate(f.created_at || f.updated_at),
                status:      existing?.status ?? 'pending',
                dbId:        existing?.id ?? null,
                topic:       existing?.module_topic ?? '',
                desc:        existing?.module_desc  ?? '',
            };
        });

    const toInsert = merged
        .filter(m => !m.dbId)
        .map(m => ({ file_name: m.storageName, file_url: m.fileUrl, status: 'pending' }));

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

function updateContentCounts() {
    setText('c-pending',  contents.filter(c => c.status === 'pending').length);
    setText('c-approved', contents.filter(c => c.status === 'approved').length);
    setText('c-rejected', contents.filter(c => c.status === 'rejected').length);
}

function getFilteredContent() {
    const filter = document.getElementById('content-status-filter')?.value || '';
    return filter ? contents.filter(c => c.status === filter) : contents;
}

const FILE_ICONS = {
    pdf:'📄', doc:'📝', docx:'📝',
    ppt:'📊', pptx:'📊',
    mp4:'🎬', jpg:'🖼️', jpeg:'🖼️', png:'🖼️',
    xls:'📗', xlsx:'📗', txt:'📃',
};

function fileExt(name) { return (name.split('.').pop() || '').toLowerCase(); }

async function loadAndRenderContent() {
    if (contentLoading) return;
    contentLoading = true;

    const body = document.getElementById('content-queue-body');
    body.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">⏳</div>
            <h4>Loading modules…</h4>
            <p>Fetching files from Supabase storage.</p>
        </div>`;
    setText('c-pending',  '…');
    setText('c-approved', '…');
    setText('c-rejected', '…');

    try {
        contents = await fetchContentsFromSupabase();
        renderContent();
    } catch (err) {
        console.error('Content load error:', err);
        body.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">⚠️</div>
                <h4>Could not load modules</h4>
                <p>${Security.escape(err.message)}</p>
                <button class="primary-btn" style="margin-top:12px"
                        onclick="loadAndRenderContent()">Retry</button>
            </div>`;
        setText('c-pending', '0'); setText('c-approved', '0'); setText('c-rejected', '0');
    } finally {
        contentLoading = false;
    }
}

function renderContent() {
    updateContentCounts();
    const body     = document.getElementById('content-queue-body');
    const filtered = getFilteredContent();

    if (!filtered.length) {
        body.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📁</div>
                <h4>No uploads found</h4>
                <p>Submitted materials from teachers will appear here for review.</p>
            </div>`;
        return;
    }

    body.innerHTML = filtered.map(c => {
        const ext   = fileExt(c.name);
        const icon  = FILE_ICONS[ext] || '📄';
        const badge =
            c.status === 'approved' ? 'badge-good'
          : c.status === 'rejected' ? 'badge-danger'
          : 'badge-warn';

        const safeId = Security.escape(String(c.id));

        const actionBtns = c.status === 'pending'
            ? `<div class="queue-actions">
                   <button class="btn-approve" onclick="approveContent('${safeId}')">✓ Approve</button>
                   <button class="btn-reject"  onclick="rejectContent('${safeId}')">✕ Reject</button>
                   <button class="btn-delete"  onclick="deleteContent('${safeId}')">🗑 Delete</button>
               </div>`
            : `<div class="queue-actions">
                   <button class="btn-reset"  onclick="resetContentStatus('${safeId}')">↺ Reset</button>
                   <button class="btn-delete" onclick="deleteContent('${safeId}')">🗑 Delete</button>
               </div>`;

        return `
        <div class="queue-item" id="queue-item-${safeId}">
            <div class="queue-file-icon">${icon}</div>
            <div class="queue-info">
                <div class="queue-name">
                    ${c.fileUrl
                        ? `<a href="${Security.escape(c.fileUrl)}" target="_blank" rel="noopener noreferrer"
                              style="color:var(--blue);text-decoration:none;font-weight:600">
                              ${Security.escape(c.name)}
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   style="width:11px;height:11px;display:inline;margin-left:3px;vertical-align:middle">
                                  <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                  <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                              </svg>
                           </a>`
                        : Security.escape(c.name)
                    }
                </div>
                <div class="queue-meta">
                    ${Security.escape(c.size)} &bull;
                    Uploaded: ${Security.escape(c.joined)} &bull;
                    <span style="text-transform:uppercase;font-weight:700;font-size:10px">.${Security.escape(ext)}</span>
                </div>
            </div>
            <span class="status-badge ${badge}" style="white-space:nowrap;flex-shrink:0">
                ${capitalize(Security.escape(c.status))}
            </span>
            ${actionBtns}
        </div>`;
    }).join('');
}

function filterContent() { renderContent(); }

async function approveContent(id) {
    const c = contents.find(x => String(x.id) === String(id));
    if (!c) return;
    const btn = document.querySelector(`#queue-item-${id} .btn-approve`);
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
    try {
        await sbUpdate(STATUS_TABLE, { file_name: c.storageName }, { status: 'approved', reviewed_at: new Date().toISOString() });
        c.status = 'approved';
        await logEvent('content', 'Material Approved', `"${c.name}" approved by admin`, 'Approved');
        renderContent(); renderHome();
        toast('success', `"${Security.escape(c.name)}" approved!`);
    } catch (err) {
        if (btn) { btn.disabled = false; btn.textContent = '✓ Approve'; }
        warn('Update Failed', err.message || 'Could not update status.');
    }
}

async function rejectContent(id) {
    const c = contents.find(x => String(x.id) === String(id));
    if (!c) return;
    const btn = document.querySelector(`#queue-item-${id} .btn-reject`);
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
    try {
        await sbUpdate(STATUS_TABLE, { file_name: c.storageName }, { status: 'rejected', reviewed_at: new Date().toISOString() });
        c.status = 'rejected';
        await logEvent('content', 'Material Rejected', `"${c.name}" rejected by admin`, 'Rejected');
        renderContent(); renderHome();
        toast('error', `"${Security.escape(c.name)}" rejected.`);
    } catch (err) {
        if (btn) { btn.disabled = false; btn.textContent = '✕ Reject'; }
        warn('Update Failed', err.message || 'Could not update status.');
    }
}

async function resetContentStatus(id) {
    const c = contents.find(x => String(x.id) === String(id));
    if (!c) return;
    Swal.fire({
        title: 'Reset to Pending?',
        text: `"${c.name}" will be moved back to the review queue.`,
        icon: 'question', showCancelButton: true,
        confirmButtonColor: '#f97316', cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Yes, reset',
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            await sbUpdate(STATUS_TABLE, { file_name: c.storageName }, { status: 'pending', reviewed_at: null });
            c.status = 'pending';
            await logEvent('content', 'Status Reset', `"${c.name}" moved back to pending`, 'Pending');
            renderContent(); renderHome();
            toast('success', 'Status reset to pending.');
        } catch (err) {
            warn('Update Failed', err.message || 'Could not reset status.');
        }
    });
}

async function deleteContent(id) {
    const c = contents.find(x => String(x.id) === String(id));
    if (!c) return;
    Swal.fire({
        title: 'Delete Module?',
        html: `"<strong>${Security.escape(c.name)}</strong>" will be permanently deleted.`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Yes, delete permanently',
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            const storageRes = await fetch(
                `${SUPABASE_URL}/storage/v1/object/${BUCKET_NAME}/${c.storageName}`,
                { method: 'DELETE', headers: { 'Authorization': `Bearer ${SUPABASE_ANON_KEY}` } }
            );
            if (!storageRes.ok) console.warn('Storage delete failed — may already be gone.');
            if (c.dbId) await sbDelete(STATUS_TABLE, { id: c.dbId });
            contents = contents.filter(x => String(x.id) !== String(id));
            await logEvent('content', 'Module Deleted', `"${c.name}" permanently deleted by admin`, 'Deleted');
            renderContent(); renderHome();
            toast('success', `"${Security.escape(c.name)}" permanently deleted.`);
        } catch (err) {
            warn('Delete Failed', err.message || 'Could not delete the module.');
        }
    });
}

/* ============================================================
   HOME
   ============================================================ */
function renderHome() {
    setText('m-total',    users.length);
    setText('m-students', users.filter(u => u.role === 'student').length);
    setText('m-teachers', users.filter(u => u.role === 'teacher').length);
    setText('m-pending',  users.filter(u => u.status === 'Pending').length);

    const logEl = document.getElementById('home-activity-log');
    if (!activity.length) {
        logEl.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><h4>No activity yet</h4><p>Events will appear here as users interact with the platform.</p></div>';
        return;
    }
    const colors = { registration:'blue-avatar', login:'green-avatar', content:'purple-avatar', system:'orange-avatar', error:'red-avatar' };
    logEl.innerHTML = activity.slice(0, 3).map(a => `
        <div class="log-item">
            <div class="log-info">
                <div class="log-avatar ${colors[a.type] || 'blue-avatar'}">${Security.escape(initials(a.title))}</div>
                <div>
                    <div class="log-title">${Security.escape(a.title)}</div>
                    <div class="log-meta">${Security.escape(a.sub)}</div>
                </div>
            </div>
            <span class="status-badge badge-new">${Security.escape(a.badge)}</span>
        </div>`).join('');
}

/* ============================================================
   ANALYTICS
   ============================================================ */
const CURRICULUM_TOPICS = ['ari','geo','har','fib','fin','div','rem','poly','rat','rad','exp','log'];

/** Load every student_progress row (platform-wide) for real analytics. */
async function loadProgressRows() {
    try {
        return await sbSelect('student_progress', '?select=session_id,topic_key,phase,score,total,passed,created_at');
    } catch (err) {
        console.warn('loadProgressRows error:', err.message);
        return [];
    }
}

async function renderAnalytics() {
    const rows = await loadProgressRows();
    const postRows = rows.filter(r => r.phase === 'post' && CURRICULUM_TOPICS.includes(r.topic_key));

    // Daily active users: distinct students with any activity today
    const todayStr = new Date().toDateString();
    const dau = new Set(rows.filter(r => new Date(r.created_at).toDateString() === todayStr).map(r => r.session_id)).size;
    setText('a-dau', dau);

    // Platform-wide average pre-test and post-test scores
    const avgOfPhase = phase => {
        const scored = rows.filter(r => r.phase === phase && CURRICULUM_TOPICS.includes(r.topic_key) && r.total > 0);
        return scored.length
            ? Math.round(scored.reduce((sum, r) => sum + (r.score / r.total) * 100, 0) / scored.length)
            : null;
    };
    const avgPre  = avgOfPhase('pre');
    const avgPost = avgOfPhase('post');
    setText('a-score-pre', avgPre  === null ? '—' : avgPre + '%');
    setText('a-score',     avgPost === null ? '—' : avgPost + '%');
    setText('a-improvement', (avgPre === null || avgPost === null) ? '—' : `${avgPost - avgPre >= 0 ? '+' : ''}${avgPost - avgPre}%`);

    // Total real topic completions across the platform
    setText('a-completions', postRows.length);

    const chartEl = document.getElementById('reg-chart');
    if (!users.length) {
        chartEl.innerHTML = '<div class="empty-state"><div class="empty-icon">📊</div><h4>No registration data yet</h4><p>Charts will populate as users join.</p></div>';
    } else {
        const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        const counts = new Array(7).fill(0);
        users.forEach(u => {
            const d = new Date(u.joined);
            if (!isNaN(d)) counts[(d.getDay() + 6) % 7]++;
        });
        const maxV = Math.max(...counts, 1);
        chartEl.innerHTML = `<div class="bar-chart">` + days.map((d, i) => `
            <div class="bar-group">
                <div class="bar-val">${counts[i]}</div>
                <div class="bar" style="height:${Math.round((counts[i]/maxV)*120)}px;background:linear-gradient(180deg,#60a5fa,#2563eb)"></div>
                <div class="bar-label">${d}</div>
            </div>`).join('') + `</div>`;
    }

    document.getElementById('subject-progress').innerHTML = postRows.length
        ? '<div class="empty-state"><div class="empty-icon">📈</div><h4>Per-subject breakdown coming soon</h4><p>Topic-level data is tracked; a subject grouping view is planned.</p></div>'
        : '<div class="empty-state"><div class="empty-icon">📈</div><h4>No progress data yet</h4><p>Data appears as students complete modules.</p></div>';

    const donutEl = document.getElementById('donut-row');
    if (!users.length) {
        donutEl.innerHTML = '<div class="empty-state"><div class="empty-icon">🍩</div><h4>No users yet</h4><p>Add users to see role distribution.</p></div>';
    } else {
        const total = users.length;
        const dist = [
            { label:'Students', count:users.filter(u=>u.role==='student').length, color:'var(--blue)',   bg:'var(--blue-light)' },
            { label:'Teachers', count:users.filter(u=>u.role==='teacher').length, color:'var(--green)',  bg:'var(--green-light)' },
            { label:'Admins',   count:users.filter(u=>u.role==='admin').length,   color:'var(--orange)', bg:'var(--orange-light)' },
            { label:'Inactive', count:users.filter(u=>u.status==='Inactive').length, color:'var(--purple)', bg:'var(--purple-light)' },
        ];
        donutEl.innerHTML = `<div class="donut-row">` + dist.map(d => `
            <div class="donut-item">
                <div class="donut-circle" style="background:${d.bg};color:${d.color}">${Math.round(d.count/total*100)}%</div>
                <div class="donut-info"><div class="donut-pct">${d.count}</div><div class="donut-lbl">${d.label}</div></div>
            </div>`).join('') + `</div>`;
    }

    // Real top-performing students, ranked by average post-test score
    const tbody = document.getElementById('top-students-tbody');
    const byStudent = new Map();
    postRows.forEach(r => {
        if (!byStudent.has(r.session_id)) byStudent.set(r.session_id, { scores: [], topics: new Set() });
        const entry = byStudent.get(r.session_id);
        if (r.total > 0) entry.scores.push((r.score / r.total) * 100);
        entry.topics.add(r.topic_key);
    });

    const ranked = users
        .filter(u => u.role === 'student')
        .map(s => {
            const entry = byStudent.get(String(s.id));
            const avg = entry?.scores.length ? Math.round(entry.scores.reduce((a, b) => a + b, 0) / entry.scores.length) : null;
            return { name: s.name, avg, modules: entry?.topics.size ?? 0 };
        })
        .filter(s => s.avg !== null)
        .sort((a, b) => b.avg - a.avg);

    if (!ranked.length) {
        tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><div class="empty-icon">🏆</div><h4>No completed quizzes yet</h4></div></td></tr>`;
    } else {
        const medals = ['🥇','🥈','🥉'];
        tbody.innerHTML = ranked.slice(0, 5).map((s, i) => `
            <tr>
                <td>${medals[i] || (i + 1)}</td>
                <td><b>${Security.escape(s.name)}</b></td>
                <td style="color:var(--green);font-weight:700">${s.avg}%</td>
                <td>${s.modules}/${CURRICULUM_TOPICS.length}</td><td>—</td>
            </tr>`).join('');
    }
}

/* ============================================================
   NAVIGATION
   ============================================================ */
function navigate(page) {
    const allowed = ['home','users','analytics','content','activity','settings'];
    if (!allowed.includes(page)) return;

    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidebar-item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));

    document.getElementById('page-' + page).classList.add('active');
    document.querySelectorAll(`[data-page="${page}"]`).forEach(b => b.classList.add('active'));
    window.scrollTo(0, 0);

    if (page === 'home')      renderHome();
    if (page === 'users')     renderUsers();
    if (page === 'analytics') renderAnalytics();
    if (page === 'content')   loadAndRenderContent();
    if (page === 'activity')  renderActivity();
    if (page === 'settings')  loadSettings();   // re-load from Supabase when tab opens
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
            Swal.fire({ icon:'success', title:'Logged out', text:'Goodbye!', timer:1500, timerProgressBar:true, showConfirmButton:false })
                .then(() => { document.getElementById('logout-form').submit(); });
        }
    });
}

/* ============================================================
   UTILITIES
   ============================================================ */
function setText(id, val)       { const el = document.getElementById(id); if (el) el.textContent = val; }
function setField(id, val)      { const el = document.getElementById(id); if (el) el.value = val; }
function setToggle(id, checked) { const el = document.getElementById(id); if (el) el.checked = checked; }
function getToggle(id)          { return document.getElementById(id)?.checked ?? false; }
function capitalize(str)        { return str.charAt(0).toUpperCase() + str.slice(1); }
function initials(str)          { return (str || '').split(' ').slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || '?'; }
function formatDate(iso) {
    if (!iso) return new Date().toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
    try { return new Date(iso).toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' }); }
    catch { return '—'; }
}
function formatSize(bytes) {
    if (!bytes)          return '—';
    if (bytes < 1024)    return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}
function timeAgo(iso) {
    if (!iso) return '—';
    const diff = Date.now() - new Date(iso).getTime();
    const m = Math.floor(diff / 60000);
    if (m < 1)   return 'just now';
    if (m < 60)  return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24)  return `${h}h ago`;
    const d = Math.floor(h / 24);
    return `${d}d ago`;
}
function makePgBtn(label, disabled, handler) {
    const btn = document.createElement('button');
    btn.className = 'pg-btn'; btn.textContent = label; btn.disabled = disabled;
    btn.addEventListener('click', handler);
    return btn;
}
function warn(title, text)  { Swal.fire({ icon:'warning', title, text, confirmButtonColor:'#2563eb' }); }
function toast(icon, title) { Swal.fire({ icon, title, timer:2000, timerProgressBar:true, showConfirmButton:false }); }

/* ============================================================
   GLOBAL EXPOSE (for onclick= attributes in Blade)
   ============================================================ */
Object.assign(window, {
    navigate,
    filterUsers, editUser, saveUser, deleteUser,
    filterContent, loadAndRenderContent,
    approveContent, rejectContent, resetContentStatus, deleteContent,
    filterActivity,
    savePlatformInfo, saveSettings, confirmDanger,
    openModal, closeModal, confirmLogout,
});

/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-page]').forEach(btn => {
        btn.addEventListener('click', () => navigate(btn.dataset.page));
    });
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape')
            document.querySelectorAll('.modal-overlay.open').forEach(o => closeModal(o.id));
    });

    // Load everything from Supabase, then render
    initDashboard();
});
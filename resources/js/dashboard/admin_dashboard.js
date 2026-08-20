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
let users      = [];
let contents   = [];
let activity   = [];
let modulesData = [];

let userEditId     = null;
let contentLoading = false;
let pendingFile     = null;
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
        tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><div class="empty-icon">👤</div><h4>No users found</h4><p>Try a different search or filter.</p></div></td></tr>`;
    } else {
        tbody.innerHTML = slice.map((u, i) => `
            <tr>
                <td style="color:var(--text-4);font-size:12px">${(userPage - 1) * USERS_PER_PAGE + i + 1}</td>
                <td><b>${Security.escape(u.name)}</b></td>
                <td style="color:var(--text-3)">${Security.escape(u.email)}</td>
                <td style="font-size:12px;color:var(--text-3)">${u.studentId ? Security.escape(u.studentId) : '—'}</td>
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
    // id arrives as a string (it's read out of an HTML onclick attribute);
    // u.id is a number from the JSON API response — normalize both sides
    // so this actually finds the row instead of silently no-op'ing.
    const u = users.find(x => String(x.id) === String(id));
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
        const u = users.find(x => String(x.id) === String(userEditId));
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
    const u = users.find(x => String(x.id) === String(id));
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
            users = users.filter(x => String(x.id) !== String(id));
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

/** Load the most recent activity logs (Home tab preview) from the authenticated backend. */
async function loadActivity() {
    try {
        const data = await apiFetch('/admin/activity');
        activity = data.data.map(r => ({
            id:    r.id,
            type:  r.type,
            title: r.title,
            sub:   r.sub   || '',
            badge: r.badge || 'Event',
            time:  r.time  || '',
        }));
    } catch (err) {
        console.warn('loadActivity error:', err.message);
    }
}

/**
 * Insert a new activity log into Supabase and prepend to the local
 * Home-tab preview array. The frontend anon key only has INSERT on
 * activity_logs (reading/deleting/archiving goes through the authenticated
 * Laravel backend), so this writes with `return=minimal` — no row comes
 * back, hence the client-generated local id below.
 */
async function logEvent(type, title, sub, badge) {
    const entry = {
        type, title, sub, badge: badge || 'Event',
        user_id:   window.__USER__?.id ?? null,
        user_name: window.__USER__?.name ?? null,
        user_role: window.__USER__?.role ?? 'admin',
    };
    try {
        await fetch(`${SUPABASE_URL}/rest/v1/${ACTIVITY_TABLE}`, {
            method: 'POST',
            headers: sbHeaders({ 'Prefer': 'return=minimal' }),
            body: JSON.stringify(entry),
        });
    } catch (err) {
        console.warn('logEvent failed:', err.message);
    }
    activity.unshift({
        id:    `local-${Date.now()}`,
        type,  title,
        sub:   sub   || '',
        badge: badge || 'Event',
        time:  'just now',
        ts:    Date.now(),
    });
    if (activity.length > 50) activity.pop();
}

/* ============================================================
   ACTIVITY LOG — search, filter, pagination, delete, archive
   Backed entirely by the authenticated Laravel endpoints under
   /admin/activity — the frontend anon key has no read/delete access to
   activity_logs, so this is the only path to view or manage it.
   ============================================================ */
const ACTIVITY_DOT_COLOR = { registration: 'blue', login: 'green', content: 'green', system: 'orange', error: 'red' };

let activityLogState = {
    data: [],
    meta: { current_page: 1, last_page: 1, total: 0 },
    filters: { q: '', type: '', role: '', from: '', to: '' },
};
let archivedLogState = {
    data: [],
    meta: { current_page: 1, last_page: 1, total: 0 },
};
let activityLogSearchTimer = null;

async function loadActivityLog(page = 1) {
    const f = activityLogState.filters;
    const params = new URLSearchParams({ page });
    if (f.q)    params.set('q', f.q);
    if (f.type) params.set('type', f.type);
    if (f.role) params.set('role', f.role);
    if (f.from) params.set('from', f.from);
    if (f.to)   params.set('to', f.to);

    try {
        const data = await apiFetch(`/admin/activity?${params.toString()}`);
        activityLogState.data = data.data;
        activityLogState.meta = data.meta;
        setText('ac-events', data.counters.eventsToday);
        setText('ac-logins', data.counters.loginsToday);
        setText('ac-errors', data.counters.errorsToday);
    } catch (err) {
        console.warn('loadActivityLog error:', err.message);
        activityLogState.data = [];
    }
    renderActivityLog();
}

function renderTimelineRows(rows, { archived }) {
    if (!rows.length) {
        return `<div class="empty-state"><div class="empty-icon">📋</div><h4>No events found</h4><p>${archived ? 'No archived logs to show yet.' : 'Try a different search or filter.'}</p></div>`;
    }
    return rows.map(a => `
        <div class="tl-item">
            <div class="tl-dot ${ACTIVITY_DOT_COLOR[a.type] || 'blue'}"></div>
            <div class="tl-body">
                <div class="tl-head">
                    <div class="tl-title">${Security.escape(a.title)}</div>
                    <div class="tl-actions">
                        ${archived ? `<button class="tbl-btn edit" onclick="restoreActivityLog('${Security.escape(a.id)}')">Restore</button>` : ''}
                        <button class="tbl-btn del" onclick="deleteActivityLog('${Security.escape(a.id)}', ${archived})">Delete</button>
                    </div>
                </div>
                <div class="tl-sub">${Security.escape(a.sub || '')}</div>
                <div class="tl-meta">
                    ${a.userRole ? `<span class="role-badge role-${Security.escape(a.userRole)}">${Security.escape(capitalize(a.userRole))}</span>` : ''}
                    <span class="tl-time">${Security.escape(a.time || '')}</span>
                </div>
            </div>
        </div>`).join('');
}

function renderPaginationInto(elId, meta, onPage) {
    const pg = document.getElementById(elId);
    if (!pg) return;
    pg.innerHTML = '';
    const { current_page: current, last_page: last } = meta;
    pg.appendChild(makePgBtn('‹ Prev', current <= 1, () => onPage(current - 1)));
    for (let i = 1; i <= last; i++) {
        const btn = makePgBtn(i, false, () => onPage(i));
        if (i === current) btn.classList.add('active');
        pg.appendChild(btn);
    }
    pg.appendChild(makePgBtn('Next ›', current >= last, () => onPage(current + 1)));
}

function renderActivityLog() {
    const tl = document.getElementById('activity-timeline');
    if (tl) tl.innerHTML = renderTimelineRows(activityLogState.data, { archived: false });
    renderPaginationInto('activity-pagination', activityLogState.meta, loadActivityLog);
}

function filterActivityLog() {
    activityLogState.filters.q    = Security.sanitize(document.getElementById('activity-search')?.value || '');
    activityLogState.filters.type = document.getElementById('activity-type-filter')?.value || '';
    activityLogState.filters.role = document.getElementById('activity-role-filter')?.value || '';
    activityLogState.filters.from = document.getElementById('activity-date-from')?.value || '';
    activityLogState.filters.to   = document.getElementById('activity-date-to')?.value || '';
    loadActivityLog(1);
}

function debounceActivitySearch() {
    clearTimeout(activityLogSearchTimer);
    activityLogSearchTimer = setTimeout(filterActivityLog, 350);
}

async function deleteActivityLog(id, fromArchive) {
    Swal.fire({
        title: 'Delete this activity log?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            await apiFetch(`/admin/activity/${id}`, { method: 'DELETE' });
            toast('success', 'Activity log deleted.');
            if (fromArchive) loadArchivedLogs(archivedLogState.meta.current_page);
            else loadActivityLog(activityLogState.meta.current_page);
        } catch (err) {
            warn('Delete Failed', err.message || 'Could not delete this log.');
        }
    });
}

async function restoreActivityLog(id) {
    try {
        await apiFetch(`/admin/activity/${id}/restore`, { method: 'POST' });
        toast('success', 'Activity log restored.');
        loadArchivedLogs(archivedLogState.meta.current_page);
        loadActivityLog(activityLogState.meta.current_page);
    } catch (err) {
        warn('Restore Failed', err.message || 'Could not restore this log.');
    }
}

const RETENTION_LABELS = { 30: '30 days', 60: '60 days', 90: '90 days', 365: '1 year' };

function openClearOldLogs() {
    Swal.fire({
        title: 'Clear Old Logs',
        html: '<p style="font-size:13px;color:#6b7280;text-align:left">Archives logs older than the period you choose. Archived logs are <b>not</b> deleted — view or restore them anytime from "Archived Logs".</p>',
        input: 'select',
        inputOptions: { 30: 'Older than 30 days', 60: 'Older than 60 days', 90: 'Older than 90 days', 365: 'Older than 1 year' },
        inputPlaceholder: 'Select a retention period',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Continue',
        cancelButtonText: 'Cancel',
        inputValidator: v => !v ? 'Please select a retention period.' : undefined,
    }).then(r => {
        if (!r.isConfirmed) return;
        const days = Number(r.value);
        Swal.fire({
            title: 'Are you sure?',
            text: `This will remove all activity logs older than ${RETENTION_LABELS[days]} from the active timeline (archived, not deleted). This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f97316',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Archive Old Logs',
            cancelButtonText: 'Cancel',
        }).then(async r2 => {
            if (!r2.isConfirmed) return;
            try {
                const result = await apiFetch('/admin/activity/archive-old', {
                    method: 'POST',
                    body: JSON.stringify({ days }),
                });
                toast('success', result.message || 'Old logs archived.');
                loadActivityLog(1);
            } catch (err) {
                warn('Archive Failed', err.message || 'Could not archive old logs.');
            }
        });
    });
}

async function loadArchivedLogs(page = 1) {
    try {
        const data = await apiFetch(`/admin/activity?archived=1&page=${page}`);
        archivedLogState.data = data.data;
        archivedLogState.meta = data.meta;
    } catch (err) {
        console.warn('loadArchivedLogs error:', err.message);
        archivedLogState.data = [];
    }
    const tl = document.getElementById('archived-timeline');
    if (tl) tl.innerHTML = renderTimelineRows(archivedLogState.data, { archived: true });
    renderPaginationInto('archived-pagination', archivedLogState.meta, loadArchivedLogs);
}

function openArchivedLogs() {
    openModal('modal-archived-logs');
    loadArchivedLogs(1);
}

/** Fetch every log matching the current Activity tab filters (unpaginated, capped) for export. */
async function fetchActivityExportRows() {
    const f = activityLogState.filters;
    const params = new URLSearchParams();
    if (f.q)    params.set('q', f.q);
    if (f.type) params.set('type', f.type);
    if (f.role) params.set('role', f.role);
    if (f.from) params.set('from', f.from);
    if (f.to)   params.set('to', f.to);
    const data = await apiFetch(`/admin/activity/export-data?${params.toString()}`);
    return data.data;
}

function activityExportRows(rows) {
    return rows.map((a, i) => [
        i + 1,
        capitalize(a.type),
        a.title,
        a.sub || '—',
        a.userName || '—',
        a.userRole ? capitalize(a.userRole) : '—',
        a.time || '—',
    ]);
}
const ACTIVITY_EXPORT_HEADERS = ['#', 'Type', 'Title', 'Details', 'User', 'Role', 'Time'];

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
        setToggle('feat-registration',  map['feat_registration']  === 'true');

    } catch (err) {
        console.warn('loadSettings error:', err.message);
    }

    // Maintenance mode's checkbox always reflects the real server state
    // (not the Supabase mirror, which can drift if `php artisan up` is
    // ever run outside this toggle).
    try {
        const status = await apiFetch('/admin/maintenance/status');
        setToggle('feat-maintenance', !!status.down);
    } catch (err) {
        console.warn('Could not load maintenance status:', err.message);
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
        // Maintenance Mode is excluded here — it takes effect immediately
        // when toggled (see initMaintenanceToggle), not on "Save Features".
        pairs = {
            feat_ai_tutor:     getToggle('feat-ai-tutor'),
            feat_leaderboard:  getToggle('feat-leaderboard'),
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

/* ============================================================
   MAINTENANCE MODE — takes effect immediately (not on "Save Features"),
   since it's a live, site-wide, high-impact switch rather than a
   preference. Confirmed before it fires either direction.
   ============================================================ */
function initMaintenanceToggle() {
    const checkbox = document.getElementById('feat-maintenance');
    if (!checkbox) return;

    checkbox.addEventListener('change', function () {
        // 'change' fires after the browser has already flipped .checked to
        // the new value, so that value IS the intended new state — unlike
        // 'click', where reading .checked mid-event is timing-dependent.
        const enabling = checkbox.checked;
        checkbox.checked = !enabling; // hold the visual state until confirmed

        Swal.fire({
            icon: enabling ? 'warning' : 'question',
            title: enabling ? 'Enable Maintenance Mode?' : 'Disable Maintenance Mode?',
            html: enabling
                ? '<p style="font-size:13px;color:#6b7280">Students and teachers will be signed out to a "under maintenance" page until you turn this back off. The admin portal stays reachable so you can undo this here.</p>'
                : '<p style="font-size:13px;color:#6b7280">The site will become accessible to everyone again.</p>',
            showCancelButton: true,
            confirmButtonColor: enabling ? '#ef4444' : '#2563eb',
            cancelButtonColor: '#6b7280',
            confirmButtonText: enabling ? 'Enable Maintenance Mode' : 'Disable Maintenance Mode',
            cancelButtonText: 'Cancel',
        }).then(async r => {
            if (!r.isConfirmed) return;
            try {
                const result = await apiFetch('/admin/maintenance/toggle', {
                    method: 'POST',
                    body: JSON.stringify({ enable: enabling }),
                });
                checkbox.checked = !!result.down;
                saveSettingsToSupabase({ feat_maintenance: checkbox.checked }).catch(() => {});
                toast('success', enabling ? 'Maintenance mode is now ON.' : 'Maintenance mode is now OFF.');
            } catch (err) {
                warn('Could Not Toggle Maintenance Mode', err.message || 'Please try again.');
            }
        });
    });
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

// Matches MODULE_TOPICS in student_dashboard.js / module.blade.php's TOPIC_ORDER.
const MODULE_GROUPS = [
    { label: 'Module 1: Sequences and Series', topics: ['ari', 'geo', 'har', 'fib', 'fin'] },
    { label: 'Module 2: Polynomials',          topics: ['div', 'rem', 'poly'] },
    { label: 'Module 3: Advanced Equations',   topics: ['rat', 'rad', 'exp', 'log'] },
];

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

    const subjectEl = document.getElementById('subject-progress');
    if (!postRows.length) {
        subjectEl.innerHTML = '<div class="empty-state"><div class="empty-icon">📈</div><h4>No progress data yet</h4><p>Data appears as students complete modules.</p></div>';
    } else {
        const activeStudents = new Set(rows.map(r => r.session_id)).size;
        subjectEl.innerHTML = MODULE_GROUPS.map(group => {
            const completedPairs = new Set(
                postRows.filter(r => group.topics.includes(r.topic_key)).map(r => `${r.session_id}:${r.topic_key}`)
            ).size;
            const possiblePairs = activeStudents * group.topics.length;
            const pct = possiblePairs ? Math.round((completedPairs / possiblePairs) * 100) : 0;
            return `
                <div class="progress-row">
                    <div class="progress-label">
                        <span>${Security.escape(group.label)}</span>
                        <span style="font-weight:800;color:${progressColor(pct)}">${pct}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:${pct}%;background:${progressColor(pct)}"></div>
                    </div>
                </div>`;
        }).join('');
    }

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
   PDF / EXCEL EXPORTS
   ============================================================ */
function buildPdfTable(title, headers, rows, filename) {
    if (!window.jspdf || !window.jspdf.jsPDF) {
        throw new Error('PDF library failed to load. Check your connection and try again.');
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const adminName = window.__USER__?.name || 'Admin';

    doc.setFontSize(18);
    doc.text(title, 14, 18);
    doc.setFontSize(10);
    doc.setTextColor(110);
    doc.text(`Generated by ${adminName} on ${new Date().toLocaleString()}`, 14, 25);

    doc.autoTable({
        startY: 32,
        head: [headers],
        body: rows.length ? rows : [['No data available.']],
        headStyles: { fillColor: [37, 99, 235] },
        styles: { fontSize: 9 },
        margin: { left: 14, right: 14 },
    });

    doc.save(filename);
}

function buildExcelTable(sheetName, headers, rows, filename) {
    if (!window.XLSX) {
        throw new Error('Spreadsheet library failed to load. Check your connection and try again.');
    }
    const wb = XLSX.utils.book_new();
    const data = [headers, ...(rows.length ? rows : [['No data available.']])];
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(data), sheetName);
    XLSX.writeFile(wb, filename);
}

function exportUsersPdf() {
    try {
        const rows = users.map((u, i) => [i + 1, u.name, u.email, u.studentId || '—', capitalize(u.role), u.joined, u.status]);
        buildPdfTable('User Management Report', ['#', 'Name', 'Email', 'Student ID', 'Role', 'Joined', 'Status'], rows, `users-report-${new Date().toISOString().slice(0, 10)}.pdf`);
        logEvent('content', 'Report Generated', 'Users PDF report was downloaded', 'Exported');
        toast('success', 'PDF report downloaded!');
    } catch (err) {
        console.error('Users PDF export error:', err);
        warn('PDF Generation Failed', err.message || 'Could not generate the PDF. Please try again.');
    }
}

function exportUsersExcel() {
    try {
        const rows = users.map((u, i) => [i + 1, u.name, u.email, u.studentId || '—', capitalize(u.role), u.joined, u.status]);
        buildExcelTable('Users', ['#', 'Name', 'Email', 'Student ID', 'Role', 'Joined', 'Status'], rows, `users-report-${new Date().toISOString().slice(0, 10)}.xlsx`);
        logEvent('content', 'Report Generated', 'Users Excel report was downloaded', 'Exported');
        toast('success', 'Excel report downloaded!');
    } catch (err) {
        console.error('Users Excel export error:', err);
        warn('Excel Generation Failed', err.message || 'Could not generate the spreadsheet. Please try again.');
    }
}

function exportModulesPdf() {
    try {
        const rows = modulesData.map((m, i) => [i + 1, m.title, m.topic, m.status, `${m.completion || 0}%`, m.date]);
        buildPdfTable('Modules Report', ['#', 'Title', 'Topic', 'Status', 'Completion', 'Date'], rows, `modules-report-${new Date().toISOString().slice(0, 10)}.pdf`);
        logEvent('content', 'Report Generated', 'Modules PDF report was downloaded', 'Exported');
        toast('success', 'PDF report downloaded!');
    } catch (err) {
        console.error('Modules PDF export error:', err);
        warn('PDF Generation Failed', err.message || 'Could not generate the PDF. Please try again.');
    }
}

function exportModulesExcel() {
    try {
        const rows = modulesData.map((m, i) => [i + 1, m.title, m.topic, m.status, `${m.completion || 0}%`, m.date]);
        buildExcelTable('Modules', ['#', 'Title', 'Topic', 'Status', 'Completion', 'Date'], rows, `modules-report-${new Date().toISOString().slice(0, 10)}.xlsx`);
        logEvent('content', 'Report Generated', 'Modules Excel report was downloaded', 'Exported');
        toast('success', 'Excel report downloaded!');
    } catch (err) {
        console.error('Modules Excel export error:', err);
        warn('Excel Generation Failed', err.message || 'Could not generate the spreadsheet. Please try again.');
    }
}

/** Exports honor the Activity tab's currently applied search/filters. */
async function exportActivityPdf() {
    try {
        const rows = activityExportRows(await fetchActivityExportRows());
        buildPdfTable('Activity Log Report', ACTIVITY_EXPORT_HEADERS, rows, `activity-report-${new Date().toISOString().slice(0, 10)}.pdf`);
        logEvent('content', 'Report Generated', 'Activity PDF report was downloaded', 'Exported');
        toast('success', 'PDF report downloaded!');
    } catch (err) {
        console.error('Activity PDF export error:', err);
        warn('PDF Generation Failed', err.message || 'Could not generate the PDF. Please try again.');
    }
}

function downloadTextFile(filename, mime, text) {
    const blob = new Blob([text], { type: mime });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}

async function exportActivityCsv() {
    try {
        if (!window.XLSX) throw new Error('Spreadsheet library failed to load. Check your connection and try again.');
        const rows = activityExportRows(await fetchActivityExportRows());
        const sheet = XLSX.utils.aoa_to_sheet([ACTIVITY_EXPORT_HEADERS, ...rows]);
        downloadTextFile(`activity-report-${new Date().toISOString().slice(0, 10)}.csv`, 'text/csv;charset=utf-8;', XLSX.utils.sheet_to_csv(sheet));
        logEvent('content', 'Report Generated', 'Activity CSV report was downloaded', 'Exported');
        toast('success', 'CSV report downloaded!');
    } catch (err) {
        console.error('Activity CSV export error:', err);
        warn('CSV Generation Failed', err.message || 'Could not generate the CSV. Please try again.');
    }
}

/** Recompute the same platform-wide metrics shown on the Analytics tab, for export. */
async function computeAnalyticsSummary() {
    const rows     = await loadProgressRows();
    const postRows = rows.filter(r => r.phase === 'post' && CURRICULUM_TOPICS.includes(r.topic_key));

    const todayStr = new Date().toDateString();
    const dau = new Set(rows.filter(r => new Date(r.created_at).toDateString() === todayStr).map(r => r.session_id)).size;

    const avgOfPhase = phase => {
        const scored = rows.filter(r => r.phase === phase && CURRICULUM_TOPICS.includes(r.topic_key) && r.total > 0);
        return scored.length
            ? Math.round(scored.reduce((sum, r) => sum + (r.score / r.total) * 100, 0) / scored.length)
            : null;
    };
    const avgPre  = avgOfPhase('pre');
    const avgPost = avgOfPhase('post');

    const days      = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const regCounts = new Array(7).fill(0);
    users.forEach(u => {
        const d = new Date(u.joined);
        if (!isNaN(d)) regCounts[(d.getDay() + 6) % 7]++;
    });

    const roleDist = [
        { label: 'Students', count: users.filter(u => u.role === 'student').length },
        { label: 'Teachers', count: users.filter(u => u.role === 'teacher').length },
        { label: 'Admins',   count: users.filter(u => u.role === 'admin').length },
        { label: 'Inactive', count: users.filter(u => u.status === 'Inactive').length },
    ];

    return { dau, avgPre, avgPost, completions: postRows.length, days, regCounts, roleDist };
}

async function exportAnalyticsPdf() {
    try {
        const s = await computeAnalyticsSummary();
        if (!window.jspdf || !window.jspdf.jsPDF) {
            throw new Error('PDF library failed to load. Check your connection and try again.');
        }
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const adminName = window.__USER__?.name || 'Admin';

        doc.setFontSize(18);
        doc.text('Analytics Report', 14, 18);
        doc.setFontSize(10);
        doc.setTextColor(110);
        doc.text(`Generated by ${adminName} on ${new Date().toLocaleString()}`, 14, 25);

        doc.autoTable({
            startY: 32,
            head: [['Metric', 'Value']],
            body: [
                ['Daily Active Users', s.dau],
                ['Avg. Pre-Test', s.avgPre === null ? '—' : `${s.avgPre}%`],
                ['Avg. Post-Test', s.avgPost === null ? '—' : `${s.avgPost}%`],
                ['Improvement', (s.avgPre === null || s.avgPost === null) ? '—' : `${s.avgPost - s.avgPre >= 0 ? '+' : ''}${s.avgPost - s.avgPre}%`],
                ['Total Completions', s.completions],
            ],
            headStyles: { fillColor: [37, 99, 235] },
            styles: { fontSize: 9 },
            margin: { left: 14, right: 14 },
        });

        doc.autoTable({
            startY: doc.lastAutoTable.finalY + 12,
            head: [['Day', 'Registrations']],
            body: s.days.map((d, i) => [d, s.regCounts[i]]),
            headStyles: { fillColor: [37, 99, 235] },
            styles: { fontSize: 9 },
            margin: { left: 14, right: 14 },
        });

        doc.autoTable({
            startY: doc.lastAutoTable.finalY + 12,
            head: [['Role', 'Count']],
            body: s.roleDist.map(d => [d.label, d.count]),
            headStyles: { fillColor: [37, 99, 235] },
            styles: { fontSize: 9 },
            margin: { left: 14, right: 14 },
        });

        doc.save(`analytics-report-${new Date().toISOString().slice(0, 10)}.pdf`);
        await logEvent('content', 'Report Generated', 'Analytics PDF report was downloaded', 'Exported');
        toast('success', 'PDF report downloaded!');
    } catch (err) {
        console.error('Analytics PDF export error:', err);
        warn('PDF Generation Failed', err.message || 'Could not generate the PDF. Please try again.');
    }
}

async function exportAnalyticsExcel() {
    try {
        const s = await computeAnalyticsSummary();
        if (!window.XLSX) {
            throw new Error('Spreadsheet library failed to load. Check your connection and try again.');
        }
        const wb = XLSX.utils.book_new();

        const summaryRows = [
            ['Metric', 'Value'],
            ['Daily Active Users', s.dau],
            ['Avg. Pre-Test', s.avgPre === null ? '—' : `${s.avgPre}%`],
            ['Avg. Post-Test', s.avgPost === null ? '—' : `${s.avgPost}%`],
            ['Improvement', (s.avgPre === null || s.avgPost === null) ? '—' : `${s.avgPost - s.avgPre >= 0 ? '+' : ''}${s.avgPost - s.avgPre}%`],
            ['Total Completions', s.completions],
        ];
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(summaryRows), 'Summary');

        const regRows = [['Day', 'Registrations'], ...s.days.map((d, i) => [d, s.regCounts[i]])];
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(regRows), 'Registrations');

        const roleRows = [['Role', 'Count'], ...s.roleDist.map(d => [d.label, d.count])];
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(roleRows), 'Role Distribution');

        XLSX.writeFile(wb, `analytics-report-${new Date().toISOString().slice(0, 10)}.xlsx`);
        await logEvent('content', 'Report Generated', 'Analytics Excel report was downloaded', 'Exported');
        toast('success', 'Excel report downloaded!');
    } catch (err) {
        console.error('Analytics Excel export error:', err);
        warn('Excel Generation Failed', err.message || 'Could not generate the spreadsheet. Please try again.');
    }
}

const EXPORTERS = {
    users:     { pdf: exportUsersPdf,     secondary: exportUsersExcel,     secondaryLabel: 'Excel' },
    analytics: { pdf: exportAnalyticsPdf, secondary: exportAnalyticsExcel, secondaryLabel: 'Excel' },
    modules:   { pdf: exportModulesPdf,   secondary: exportModulesExcel,   secondaryLabel: 'Excel' },
    activity:  { pdf: exportActivityPdf,  secondary: exportActivityCsv,    secondaryLabel: 'CSV' },
};

/** Single "Export" button entry point — lets the admin pick a format. */
function showExportPicker(section) {
    const exporter = EXPORTERS[section];
    if (!exporter) return;

    Swal.fire({
        title: 'Export Report',
        text: 'Choose a format to download.',
        icon: 'question',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'PDF',
        denyButtonText: exporter.secondaryLabel,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb',
        denyButtonColor: '#16a34a',
    }).then(r => {
        if (r.isConfirmed) exporter.pdf();
        else if (r.isDenied) exporter.secondary();
    });
}

/* ============================================================
   NAVIGATION
   ============================================================ */
function navigate(page) {
    const allowed = ['home','users','analytics','content','modules','activity','settings'];
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
    if (page === 'modules')   loadAndRenderModules();
    if (page === 'activity')  loadActivityLog(1);
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
function progressColor(pct) {
    if (pct >= 80) return '#2563eb';
    if (pct >= 60) return '#10b981';
    if (pct >= 40) return '#f97316';
    return '#ef4444';
}
function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
function topicTheme(topic) {
    if (topic.includes('Module 1') || topic.includes('Sequences')) return 'blue-theme';
    if (topic.includes('Module 2') || topic.includes('Polynomials')) return 'green-theme';
    if (topic.includes('Module 3') || topic.includes('Advanced')) return 'orange-theme';
    return 'blue-theme';
}

/* ============================================================
   MODULES — full CRUD over the same "modules" bucket / module_status
   table that the teacher's own Modules tab uses. Admin uploads land
   as "pending" just like a teacher's, and go through the same
   Content Management approval queue.
   ============================================================ */

/** Guess the module group from the file title keywords */
function guessModuleTopic(title) {
    const t = title.toLowerCase();
    if (/arithmetic|geometric|harmonic|fibonacci|finite|infinite|sequence|series/.test(t))
        return 'Module 1: Sequences and Series';
    if (/polynomial|remainder|factor|division/.test(t))
        return 'Module 2: Polynomials';
    if (/rational|radical|exponential|logarithm|system/.test(t))
        return 'Module 3: Advanced Equations';
    return 'General';
}

async function uploadFileToSupabase(file) {
    const safeFileName = file.name.replace(/[^a-zA-Z0-9.\-_]/g, '_');
    const filePath      = `${Date.now()}_${safeFileName}`;
    const uploadUrl     = `${SUPABASE_URL}/storage/v1/object/${BUCKET_NAME}/${filePath}`;

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

async function deleteFileFromSupabase(filePath) {
    const res = await fetch(`${SUPABASE_URL}/storage/v1/object/${BUCKET_NAME}/${filePath}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${SUPABASE_ANON_KEY}` },
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `Delete failed (${res.status})`);
    }
    return true;
}

async function fetchModulesFromSupabase() {
    const listRes = await fetch(`${SUPABASE_URL}/storage/v1/object/list/${BUCKET_NAME}`, {
        method: 'POST',
        headers: sbHeaders(),
        body: JSON.stringify({ prefix: '', limit: 200, offset: 0 }),
    });
    if (!listRes.ok) {
        const err = await listRes.json().catch(() => ({}));
        throw new Error(err.message || `Failed to list bucket (${listRes.status})`);
    }
    const files = await listRes.json();

    let statusRows = [];
    try {
        statusRows = await sbSelect(STATUS_TABLE, '?select=*');
    } catch (e) {
        console.warn('Could not fetch module_status table:', e.message);
    }

    const statusMap = {};
    statusRows.forEach(r => { statusMap[r.file_name] = r; });

    const merged = files
        .filter(f => f.name && !f.name.startsWith('.'))
        .map((f, idx) => {
            const publicUrl = `${SUPABASE_URL}/storage/v1/object/public/${BUCKET_NAME}/${encodeURIComponent(f.name)}`;
            const rawName   = f.name.replace(/^\d+_/, '');
            const fileTitle = rawName.replace(/\.[^/.]+$/, '');
            const existing  = statusMap[f.name];
            const dbStatus  = existing?.status ?? 'pending';

            const title = existing?.module_title ?? fileTitle;
            const topic = existing?.module_topic ?? guessModuleTopic(fileTitle);
            const desc  = existing?.module_desc ?? '';

            const displayStatus =
                dbStatus === 'approved' ? 'Published' :
                dbStatus === 'rejected' ? 'Rejected'  :
                'Pending Review';

            return {
                id:          f.id || idx + 1,
                storageName: f.name,
                title, desc, topic,
                status:      displayStatus,
                dbStatus,
                completion:  0,
                date:        formatDate(f.created_at || f.updated_at),
                fileName:    rawName,
                fileSize:    f.metadata?.size ?? 0,
                fileUrl:     publicUrl,
                dbId:        existing?.id ?? null,
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

function getFilteredModules() {
    const q     = Security.sanitize(document.getElementById('module-search')?.value || '').toLowerCase();
    const topic = document.getElementById('module-topic-filter')?.value || '';
    return modulesData.filter(m =>
        m.title.toLowerCase().includes(q) &&
        (!topic || m.topic === topic)
    );
}

function moduleBadgeClass(status) {
    if (status === 'Published') return 'badge-good';
    if (status === 'Rejected')  return 'badge-danger';
    return 'badge-average';
}

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
    setText('mod-total', '…'); setText('mod-published', '…');
    setText('mod-draft', '…'); setText('mod-completion', '…');

    try {
        modulesData = await fetchModulesFromSupabase();
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
                    <button class="primary-btn" style="margin-top:12px" onclick="loadAndRenderModules()">Retry</button>
                </div>`;
        }
        setText('mod-total', '0'); setText('mod-published', '0');
        setText('mod-draft', '0'); setText('mod-completion', '0%');
    }
}

function renderModules() {
    const published = modulesData.filter(m => m.status === 'Published').length;
    const pending    = modulesData.filter(m => m.status === 'Pending Review').length;
    const rejected   = modulesData.filter(m => m.status === 'Rejected').length;
    const avgComp    = modulesData.length
        ? Math.round(modulesData.reduce((s, m) => s + (m.completion || 0), 0) / modulesData.length)
        : 0;

    setText('mod-total', modulesData.length);
    setText('mod-published', published);
    setText('mod-draft', pending + rejected);
    setText('mod-completion', avgComp + '%');

    const grid     = document.getElementById('modules-grid');
    const filtered = getFilteredModules();

    if (!filtered.length) {
        grid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h4>${modulesData.length ? 'No modules match your search' : 'No modules uploaded yet'}</h4>
                <p>${modulesData.length ? 'Try a different search or topic filter.' : 'Click "+ Add Module" to upload the first file.'}</p>
            </div>`;
        return;
    }

    grid.innerHTML = filtered.map(m => {
        const fileBadge = m.fileName
            ? `<span class="module-file-badge">
                 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;flex-shrink:0">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                 </svg>
                 ${m.fileUrl
                     ? `<a href="${Security.escape(m.fileUrl)}" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline">${Security.escape(m.fileName)}</a>`
                     : Security.escape(m.fileName)
                 }
               </span>`
            : '';

        const rejectedHint = m.status === 'Rejected'
            ? `<div style="margin-top:6px;padding:6px 10px;background:#fef2f2;border-radius:6px;font-size:11px;color:#dc2626;font-weight:600">⚠️ Marked rejected — edit and re-save, or delete.</div>`
            : '';
        const pendingHint = m.status === 'Pending Review'
            ? `<div style="margin-top:6px;padding:6px 10px;background:#fff7ed;border-radius:6px;font-size:11px;color:#ea580c;font-weight:600">🕐 Awaiting approval in Content Management before students can access it.</div>`
            : '';

        return `
        <div class="module-card">
            <div class="module-card-header">
                <div class="module-icon-wrap ${topicTheme(m.topic)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
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
                    <button class="tbl-btn edit" onclick="editModule('${Security.escape(String(m.id))}')">Edit</button>
                    <button class="tbl-btn feedback" onclick="deleteModule('${Security.escape(String(m.id))}')">Delete</button>
                </div>
            </div>
        </div>`;
    }).join('');
}

function filterModules() { renderModules(); }

function cancelModule() {
    document.getElementById('mod-title').value  = '';
    document.getElementById('mod-desc').value   = '';
    document.getElementById('mod-topic').value  = 'Module 1: Sequences and Series';
    document.getElementById('mod-status').value = 'Draft';
    document.getElementById('mod-edit-id').value = '';
    document.getElementById('mod-modal-title').textContent = 'Add Module';
    clearFile();
    closeModal('modal-add-module');
}

function openAddModule() {
    document.getElementById('mod-title').value  = '';
    document.getElementById('mod-desc').value   = '';
    document.getElementById('mod-topic').value  = 'Module 1: Sequences and Series';
    document.getElementById('mod-status').value = 'Draft';
    document.getElementById('mod-edit-id').value = '';
    document.getElementById('mod-modal-title').textContent = 'Add Module';
    clearFile();
    openModal('modal-add-module');
}

async function saveModule() {
    const title  = Security.sanitize(document.getElementById('mod-title').value.trim());
    const desc   = Security.sanitize(document.getElementById('mod-desc').value.trim());
    const topic  = document.getElementById('mod-topic').value.trim();
    const editId = document.getElementById('mod-edit-id').value.trim();

    if (!title || title.length < 3)
        return warn('Title required', 'Please enter a module title (at least 3 characters).');

    const saveBtn = document.querySelector('#modal-add-module .btn-save');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }

    try {
        let fileName = null, fileSize = null, fileUrl = null, storageName = null;

        if (pendingFile) {
            const MAX_BYTES = 20 * 1024 * 1024;
            if (pendingFile.size > MAX_BYTES) {
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Module'; }
                return warn('File too large', 'Please select a file smaller than 20 MB.');
            }
            const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg'];
            const ext = pendingFile.name.split('.').pop()?.toLowerCase();
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

        if (editId) {
            const moduleIndex = modulesData.findIndex(m => String(m.id) === String(editId));
            if (moduleIndex === -1) {
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Module'; }
                return warn('Error', 'Module not found in local data.');
            }
            const existing = modulesData[moduleIndex];

            modulesData[moduleIndex] = {
                ...existing, title, desc, topic,
                fileName:    pendingFile ? fileName    : existing.fileName,
                fileSize:    pendingFile ? fileSize    : existing.fileSize,
                fileUrl:     pendingFile ? fileUrl     : existing.fileUrl,
                storageName: pendingFile ? storageName : existing.storageName,
            };

            if (existing.dbId) {
                try {
                    if (saveBtn) saveBtn.textContent = 'Updating database…';
                    await sbUpdate(STATUS_TABLE, { id: existing.dbId }, {
                        module_title: title,
                        module_desc: desc,
                        module_topic: topic,
                        ...(pendingFile && { file_name: storageName, file_url: fileUrl }),
                    });
                } catch (err) {
                    warn('Save Failed', `Could not save to database: ${err.message}`);
                    if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Module'; }
                    return;
                }
            } else {
                try {
                    const inserted = await sbUpsert(STATUS_TABLE, [{
                        file_name: existing.storageName || null,
                        file_url: existing.fileUrl || null,
                        status: 'pending',
                        module_title: title,
                        module_desc: desc,
                        module_topic: topic,
                    }]);
                    if (Array.isArray(inserted) && inserted[0]) modulesData[moduleIndex].dbId = inserted[0].id;
                } catch (err) {
                    console.warn('Upsert fallback failed:', err.message);
                }
            }

            await logEvent('content', 'Module Updated', `"${title}" was edited by admin`, 'Updated');
            pendingFile = null;
            closeModal('modal-add-module');
            renderModules();
            toast('success', `"${Security.escape(title)}" updated successfully!`);
        } else {
            if (saveBtn) saveBtn.textContent = 'Saving to database…';
            let dbId = null;
            try {
                const inserted = await sbUpsert(STATUS_TABLE, [{
                    file_name: storageName || null,
                    file_url: fileUrl || null,
                    status: 'pending',
                    module_title: title,
                    module_desc: desc,
                    module_topic: topic,
                }]);
                if (Array.isArray(inserted) && inserted[0]) dbId = inserted[0].id;
            } catch (err) {
                console.warn('Could not create database row:', err.message);
            }

            modulesData.push({
                id: Date.now(), storageName: storageName || null,
                title, desc, topic,
                status: 'Pending Review', dbStatus: 'pending', completion: 0,
                date: formatDate(new Date().toISOString()),
                fileName: fileName || null, fileSize: fileSize || 0, fileUrl: fileUrl || null,
                dbId,
            });

            await logEvent('content', 'Module Uploaded', `"${title}" uploaded by admin`, 'Created');
            pendingFile = null;
            closeModal('modal-add-module');
            renderModules();
            toast('success', `"${Security.escape(title)}" uploaded!`);
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
                       : Security.escape(m.fileName)}
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
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:12px;color:#6b7280;font-weight:600">Status</span>
                        <span style="font-size:12px;font-weight:700">${Security.escape(m.status)}</span>
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

    document.getElementById('mod-title').value  = m.title || '';
    document.getElementById('mod-desc').value   = m.desc || '';
    document.getElementById('mod-topic').value  = m.topic || 'Module 1: Sequences and Series';
    document.getElementById('mod-status').value = 'Draft';
    document.getElementById('mod-edit-id').value = String(m.id);
    document.getElementById('mod-modal-title').textContent = 'Edit Module';

    pendingFile = null;
    const fileInput  = document.getElementById('mod-file');
    const uploadArea = document.getElementById('mod-file-area');
    if (fileInput) fileInput.value = '';

    if (m.fileName) {
        const filePreview     = document.getElementById('mod-file-preview');
        const filePreviewName = document.getElementById('mod-file-name');
        const filePreviewSize = document.getElementById('mod-file-size');
        if (filePreview) filePreview.classList.add('visible');
        if (uploadArea) uploadArea.style.display = 'none';
        if (filePreviewName) filePreviewName.textContent = m.fileName;
        if (filePreviewSize) filePreviewSize.textContent = formatFileSize(m.fileSize);
        if (fileInput) fileInput.setAttribute('data-existing-file', m.storageName);
    } else {
        const filePreview = document.getElementById('mod-file-preview');
        if (filePreview) filePreview.classList.remove('visible');
        if (uploadArea) uploadArea.style.display = '';
        if (fileInput) fileInput.removeAttribute('data-existing-file');
    }

    openModal('modal-add-module');
}

async function deleteModule(id) {
    const m = modulesData.find(x => String(x.id) === String(id));
    if (!m) return;
    Swal.fire({
        title: 'Delete Module?',
        text: `"${m.title}" will be permanently deleted, including its file.`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Delete', cancelButtonText: 'Cancel',
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            if (m.storageName) {
                try {
                    await deleteFileFromSupabase(m.storageName);
                } catch (fileErr) {
                    console.warn('Could not delete file from storage:', fileErr.message);
                }
            }
            if (m.dbId) {
                try {
                    await sbDelete(STATUS_TABLE, { id: m.dbId });
                } catch (dbErr) {
                    console.warn('Could not delete from database:', dbErr.message);
                }
            }
            modulesData = modulesData.filter(x => String(x.id) !== String(id));
            await logEvent('content', 'Module Deleted', `"${m.title}" permanently deleted by admin`, 'Deleted');
            renderModules();
            toast('success', 'Module permanently deleted.');
        } catch (err) {
            console.error('Delete error:', err);
            warn('Delete Failed', err.message || 'Could not delete the module.');
        }
    });
}

/* ============================================================
   FILE UPLOAD HANDLER (Add/Edit Module modal)
   ============================================================ */
function clearFile() {
    pendingFile = null;
    const fileInput  = document.getElementById('mod-file');
    const uploadArea = document.getElementById('mod-file-area');
    const preview    = document.getElementById('mod-file-preview');

    if (fileInput) { fileInput.value = ''; fileInput.removeAttribute('data-existing-file'); }
    if (preview) preview.classList.remove('visible');
    if (uploadArea) { uploadArea.style.display = ''; uploadArea.style.removeProperty('display'); }
}

function initFileUpload() {
    const fileInput   = document.getElementById('mod-file');
    const uploadArea  = document.getElementById('mod-file-area');
    const preview     = document.getElementById('mod-file-preview');
    const previewName = document.getElementById('mod-file-name');
    const previewSize = document.getElementById('mod-file-size');
    const removeBtn   = document.getElementById('mod-file-remove');

    if (!fileInput) return;

    function showPreview(file) {
        pendingFile = file;
        previewName.textContent = file.name;
        previewSize.textContent = formatFileSize(file.size);
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
   GLOBAL EXPOSE (for onclick= attributes in Blade)
   ============================================================ */
Object.assign(window, {
    navigate,
    filterUsers, editUser, saveUser, deleteUser,
    filterContent, loadAndRenderContent,
    approveContent, rejectContent, resetContentStatus, deleteContent,
    filterModules, loadAndRenderModules, openAddModule, cancelModule, saveModule,
    viewModule, editModule, deleteModule, clearFile,
    filterActivityLog, debounceActivitySearch, deleteActivityLog, restoreActivityLog,
    openClearOldLogs, openArchivedLogs,
    savePlatformInfo, saveSettings, confirmDanger,
    openModal, closeModal, confirmLogout,
    showExportPicker,
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
    initFileUpload();
    initMaintenanceToggle();

    // Load everything from Supabase, then render
    initDashboard();
});
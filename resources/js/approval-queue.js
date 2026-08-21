/* ================================
   resources/js/approval-queue.js
   Shared live-poller for the two "approval queue" pages (Teacher Approvals
   for admins, Student Approvals for teachers) — both server-rendered Blade
   pages with no other JS. Polls a small JSON endpoint and updates the
   Pending/Approved/Rejected badge counts plus the Pending table body in
   place, so a new signup shows up without a manual reload. Approve/Reject
   stay regular form submits (a deliberate action, not passive data).
   ================================ */

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function renderPendingRow(row, columns) {
    const cells = columns.map(col => {
        if (col === 'studentId') return `<td>${row.studentId ? escapeHtml(row.studentId) : '—'}</td>`;
        if (col === 'name') return `<td>${escapeHtml(row.name)}</td>`;
        if (col === 'email') return `<td>${escapeHtml(row.email)}</td>`;
        if (col === 'requestedAgo') return `<td>${escapeHtml(row.requestedAgo)}</td>`;
        return '';
    }).join('');

    return `
        <tr>
            ${cells}
            <td class="actions">
                <form method="POST" action="${escapeHtml(row.approveUrl)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken())}">
                    <button type="submit" class="btn btn-approve">Approve</button>
                </form>
                <form method="POST" action="${escapeHtml(row.rejectUrl)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken())}">
                    <button type="submit" class="btn btn-reject">Reject</button>
                </form>
            </td>
        </tr>`;
}

/**
 * @param {object} config
 * @param {string} config.dataUrl
 * @param {string} config.pendingBadgeId
 * @param {string} config.approvedBadgeId
 * @param {string} config.rejectedBadgeId
 * @param {string} config.pendingTbodyId
 * @param {string[]} config.columns - cell order for each pending row, e.g. ['name','email','studentId','requestedAgo']
 * @param {number} config.emptyColspan
 * @param {string} config.emptyMessage
 */
function initApprovalQueuePolling(config) {
    const pendingTbody = document.getElementById(config.pendingTbodyId);
    // Only replace the Pending table body while it's still showing page 1 —
    // if the reviewer has paginated further in, a background refresh must
    // never reshuffle the page out from under them.
    const onPendingPageOne = pendingTbody?.dataset.page === '1';

    let last = null;

    async function tick() {
        let data;
        try {
            data = await pollJson(config.dataUrl);
        } catch (e) {
            console.warn('[approval-queue] poll failed:', e.message);
            return;
        }
        if (data !== null) {
            last = data;
        }
        if (!last) return;

        setBadge(config.pendingBadgeId, last.pendingCount);
        setBadge(config.approvedBadgeId, last.approvedCount);
        setBadge(config.rejectedBadgeId, last.rejectedCount);

        if (onPendingPageOne && pendingTbody) {
            pendingTbody.innerHTML = last.pending.length
                ? last.pending.map(row => renderPendingRow(row, config.columns)).join('')
                : `<tr class="empty-row"><td colspan="${config.emptyColspan}">${escapeHtml(config.emptyMessage)}</td></tr>`;
        }
    }

    function setBadge(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = String(value);
    }

    startPolling(tick, 30000);
}

window.initApprovalQueuePolling = initApprovalQueuePolling;

/* ================================
   resources/js/polling.js
   Small, shared, dependency-free interval poller for "this might have
   changed" endpoints — used by every dashboard that needs to refresh data
   without a manual page reload. No new libraries; framework-native HTTP
   conditional requests (ETag / 304) do the heavy lifting server-side.
   ================================ */

/** Remembers the last ETag we saw per URL, so repeat requests can ask the
 *  server "has this changed since I last asked?" instead of re-downloading
 *  data that's still the same. */
const etagCache = new Map();

/**
 * Fetches JSON with automatic If-None-Match support.
 * Returns the parsed body on 200, or `null` on 304 (nothing changed —
 * callers should keep using whatever they already have).
 * Throws on network errors or non-2xx/304 statuses, so startPolling()
 * below can detect failures and back off.
 */
async function pollJson(url) {
    const headers = { Accept: 'application/json' };
    const knownEtag = etagCache.get(url);
    if (knownEtag) headers['If-None-Match'] = knownEtag;

    const res = await fetch(url, { headers, credentials: 'same-origin' });

    if (res.status === 304) return null;
    if (!res.ok) throw new Error(`${url} responded ${res.status}`);

    const etag = res.headers.get('ETag');
    if (etag) etagCache.set(url, etag);

    return res.json();
}

/**
 * Runs `fn` on a repeating interval.
 * - Pauses entirely while the browser tab is hidden (document.visibilityState),
 *   and refreshes immediately + resumes the normal cadence when it becomes
 *   visible again — so nothing goes stale while you were looking away, but
 *   nothing polls in the background either.
 * - On failure, doubles the interval (capped at 8x) so a struggling server
 *   isn't hammered; resets back to normal on the next success.
 * Returns { stop() } to cancel.
 */
function startPolling(fn, intervalMs) {
    let timer = null;
    let currentInterval = intervalMs;
    let stopped = false;
    const maxInterval = intervalMs * 8;

    async function tick() {
        try {
            await fn();
            currentInterval = intervalMs;
        } catch (e) {
            currentInterval = Math.min(currentInterval * 2, maxInterval);
            console.warn('[polling] tick failed, backing off to', currentInterval, 'ms —', e.message);
        }
        schedule();
    }

    function schedule() {
        clearTimeout(timer);
        if (stopped || document.visibilityState !== 'visible') return;
        timer = setTimeout(tick, currentInterval);
    }

    function onVisibilityChange() {
        clearTimeout(timer);
        if (stopped) return;
        if (document.visibilityState === 'visible') {
            currentInterval = intervalMs; // don't resume mid-backoff after being away
            tick();
        }
    }

    document.addEventListener('visibilitychange', onVisibilityChange);
    if (document.visibilityState === 'visible') schedule();

    return {
        stop() {
            stopped = true;
            clearTimeout(timer);
            document.removeEventListener('visibilitychange', onVisibilityChange);
        },
    };
}

window.pollJson = pollJson;
window.startPolling = startPolling;

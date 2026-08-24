/* ================================
   resources/js/nav-progress.js
   Thin top-of-page progress bar that appears the instant a real,
   same-tab, cross-page link (or form submit) fires — so navigation
   feels acknowledged immediately instead of a silent gap before the
   next page paints. Deliberately does nothing for the dashboards'
   in-page tab-switching (navigate(page) toggles CSS classes, it's
   never an <a href> click), only for genuine full-page navigations:
   the Modules page, the approval-queue pages, login/logout, homepage
   links, etc.

   Self-contained (injects its own <style>) so one <script> include is
   enough per page — no separate CSS entry point to keep in sync
   across the several Blade views this loads on. Colors are the same
   #2563eb / #10b981 already used everywhere in this app for blue and
   green (dashboard CSS --blue/--green, the auth pages' gradient) —
   not a new palette, just the one value repeated where a CSS custom
   property isn't guaranteed to be in scope on every page this runs on.
   ================================ */

(function () {
    const BAR_ID = 'nav-progress-bar';

    function injectStyles() {
        if (document.getElementById('nav-progress-styles')) return;
        const style = document.createElement('style');
        style.id = 'nav-progress-styles';
        style.textContent = `
            #${BAR_ID} {
                position: fixed;
                top: 0;
                left: 0;
                height: 3px;
                width: 0%;
                background: linear-gradient(90deg, #2563eb, #10b981);
                z-index: 99999;
                transition: width 0.3s ease, opacity 0.2s ease;
                opacity: 0;
                pointer-events: none;
            }
            #${BAR_ID}.active { opacity: 1; }
        `;
        document.head.appendChild(style);
    }

    function getBar() {
        let bar = document.getElementById(BAR_ID);
        if (!bar) {
            bar = document.createElement('div');
            bar.id = BAR_ID;
            document.body.appendChild(bar);
        }
        return bar;
    }

    function start() {
        injectStyles();
        const bar = getBar();
        bar.style.width = '0%';
        bar.classList.add('active');
        // Force a reflow so the width transition below animates from 0
        // instead of jumping straight to 80%.
        void bar.offsetWidth;
        bar.style.width = '80%';
    }

    /**
     * Only real, same-tab, cross-page navigations — not hash links,
     * new-tab links, downloads, mailto/tel, or an explicit opt-out via
     * data-no-progress on the link itself.
     */
    function isRealNavigation(link) {
        if (!link || !link.href) return false;
        if (link.target && link.target !== '_self') return false;
        if (link.hasAttribute('download')) return false;
        if (link.dataset.noProgress !== undefined) return false;

        const rawHref = link.getAttribute('href') || '';
        if (rawHref.startsWith('#')) return false;

        let url;
        try {
            url = new URL(link.href, window.location.href);
        } catch {
            return false;
        }
        if (['javascript:', 'mailto:', 'tel:'].includes(url.protocol)) return false;
        if (url.origin !== window.location.origin) return false;
        // Same path+query, only the hash differs — not a real navigation.
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;

        return true;
    }

    document.addEventListener('click', e => {
        const link = e.target.closest('a');
        if (isRealNavigation(link)) start();
    });

    // A couple of real navigations in this app happen via
    // `window.location.href = ...` in JS rather than a clickable <a> (e.g.
    // the student dashboard's Modules link) — those can't be picked up by
    // the click listener above, so expose the same start() for them to
    // call directly right before assigning location.href.
    window.startNavProgress = start;

    // Form submits (logout, approve/reject on the approval-queue pages,
    // etc.) also leave the page — same acknowledgement, with its own
    // opt-out for anything that submits via fetch/AJAX instead.
    document.addEventListener('submit', e => {
        const form = e.target;
        if (form instanceof HTMLFormElement && form.dataset.noProgress === undefined) start();
    });

    // If the page is restored from the back/forward cache instead of a
    // fresh load, make sure a bar left mid-animation doesn't linger.
    window.addEventListener('pageshow', () => {
        const bar = document.getElementById(BAR_ID);
        if (bar) {
            bar.style.width = '100%';
            bar.classList.remove('active');
        }
    });
})();

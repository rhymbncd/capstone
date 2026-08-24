/* ================================
   resources/js/dashboard/math-panel.js
   Wraps the existing Math AI Assistant widget (chatbot.js, untouched)
   with: a Chat/Calculator tab bar, a draggable panel (Pointer Events),
   and the accessibility wiring the two didn't need before (aria-expanded
   on the FAB, focus management, Escape-to-close, arrow-key tabs).

   The calculator's numeric engine (math.js, fractions, degree scope)
   lives in calculator-engine.js and is loaded lazily via loadMathEngine()
   — only once the Calculator tab is actually opened, never at page load.

   Depends on chatbot.js already having run and exposed window.openChat /
   window.closeChat — safe because Vite/laravel-vite-plugin emits module
   <script> tags in the input-array order given in vite.config.js, and
   this file is listed after chatbot.js there, so its DOMContentLoaded
   listener registers (and therefore fires) after chatbot.js's.
   ================================ */

import {
    loadMathEngine,
    evaluateExpression,
    roundDisplay,
    toFraction,
    formatFraction,
} from './calculator-engine.js';

const STORAGE_KEY = 'mathPanelPosition';
const MOBILE_BREAKPOINT = 640;
const CLAMP_MARGIN = 8;
const KEYBOARD_INSERT_PATTERN = /^[0-9.+\-*/^!()]$/;

document.addEventListener('DOMContentLoaded', function () {

    const panel = document.getElementById('ai-chat-window');
    const fab = document.getElementById('fab-chat');

    // Widget isn't included on this page (e.g. teacher/admin dashboards) — nothing to wire up.
    if (!panel || !fab) {
        return;
    }

    const dragHandle = document.getElementById('chat-drag-handle');
    const resetBtn = document.getElementById('chat-reset-position');
    const tablist = panel.querySelector('.chat-tablist');
    const tabChat = document.getElementById('tab-chat');
    const tabCalculator = document.getElementById('tab-calculator');
    const panelChat = document.getElementById('panel-chat');
    const panelCalculator = document.getElementById('panel-calculator');

    // ============================================
    // FAB / PANEL ACCESSIBILITY
    // ============================================

    fab.setAttribute('aria-label', 'Open Math AI Assistant');
    fab.setAttribute('aria-expanded', 'false');
    fab.setAttribute('aria-controls', 'ai-chat-window');

    const openStateObserver = new MutationObserver(() => {
        const isOpen = panel.classList.contains('open');

        fab.setAttribute('aria-expanded', String(isOpen));

        if (isOpen) {
            (document.activeElement === document.body) && tabChat?.focus();
        } else {
            fab.focus();
        }
    });

    openStateObserver.observe(panel, { attributes: true, attributeFilter: ['class'] });

    // Move focus into the panel the moment it opens (openChat() itself
    // already focuses #ai-input on the Chat tab after its own timeout —
    // this covers the general case, including opening straight to a
    // panel whose focusable target isn't the chat input).
    fab.addEventListener('click', () => {
        requestAnimationFrame(() => tabChat?.focus());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.classList.contains('open')) {
            window.closeChat?.();
        }
    });

    // ============================================
    // TABS
    // ============================================

    const tabs = [tabChat, tabCalculator].filter(Boolean);
    const panelsByTabId = {
        'tab-chat': panelChat,
        'tab-calculator': panelCalculator,
    };

    function activateTab(tab) {
        if (!tab) {
            return;
        }

        tabs.forEach((candidate) => {
            const isActive = candidate === tab;

            candidate.classList.toggle('active', isActive);
            candidate.setAttribute('aria-selected', String(isActive));
            candidate.tabIndex = isActive ? 0 : -1;

            const associatedPanel = panelsByTabId[candidate.id];

            if (associatedPanel) {
                associatedPanel.hidden = !isActive;
            }
        });

        tab.focus();

        if (tab === tabCalculator) {
            ensureCalculatorReady();
        }
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => activateTab(tab));
    });

    document.getElementById('chat-calc-hint')?.addEventListener('click', () => {
        activateTab(tabCalculator);
    });

    tablist?.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }

        event.preventDefault();

        const currentIndex = tabs.indexOf(document.activeElement);

        if (currentIndex === -1) {
            return;
        }

        const delta = event.key === 'ArrowRight' ? 1 : -1;
        const nextTab = tabs[(currentIndex + delta + tabs.length) % tabs.length];

        activateTab(nextTab);
    });

    // ============================================
    // CALCULATOR — lazy engine load + state
    // ============================================

    const expressionEl = document.getElementById('calc-expression');
    const outputEl = document.getElementById('calc-preview');
    const errorEl = document.getElementById('calc-error');
    const loadingEl = document.getElementById('calc-loading');
    const keypad = document.getElementById('calc-keypad');
    const angleBtn = document.getElementById('calc-angle-btn');
    const formatBtn = document.getElementById('calc-format-btn');
    const askBtn = document.getElementById('calc-ask-btn');

    let mathEngine = null;
    let engineLoadStarted = false;
    let angleMode = 'deg';
    let displayMode = 'fraction';
    let expression = '';
    let justEvaluated = false;
    let lastResultValue = null;
    let lastResultDisplay = null;
    let lastExpressionText = null;

    function ensureCalculatorReady() {
        if (engineLoadStarted) {
            return;
        }

        engineLoadStarted = true;

        setKeypadDisabled(true);
        loadingEl.hidden = false;
        loadingEl.textContent = 'Loading calculator…';

        loadMathEngine()
            .then((math) => {
                mathEngine = math;
                loadingEl.hidden = true;
                setKeypadDisabled(false);
            })
            .catch((error) => {
                console.error('Calculator engine failed to load:', error);
                loadingEl.textContent = 'Calculator failed to load. Please check your connection and reopen this tab.';
            });
    }

    function setKeypadDisabled(disabled) {
        keypad?.querySelectorAll('button').forEach((button) => {
            button.disabled = disabled;
        });
    }

    function renderExpressionLine() {
        expressionEl.textContent = expression;
    }

    function formatDisplayValue(rounded) {
        if (displayMode !== 'fraction') {
            return String(rounded);
        }

        const fraction = toFraction(rounded);

        return fraction ? formatFraction(fraction) : String(rounded);
    }

    /** Live output while typing: an incomplete/invalid expression (e.g.
     *  "3+") just leaves the last valid value on screen rather than
     *  flickering blank, like a normal calculator's incremental eval. */
    function updateLiveOutput() {
        errorEl.textContent = '';

        if (!mathEngine || !expression) {
            outputEl.textContent = '0';
            return;
        }

        const result = evaluateExpression(mathEngine, expression, angleMode);

        if (result.ok) {
            outputEl.textContent = formatDisplayValue(roundDisplay(result.value));
        }
    }

    /** Applies a computed result to both the visible output and the
     *  expression buffer that carries into the next calculation — using
     *  the exact fraction form (e.g. "(1/3)") rather than its rounded
     *  decimal, so chained fraction arithmetic never drifts. */
    function applyResult(rounded) {
        let displayText;
        let continuation;

        if (displayMode === 'fraction') {
            const fraction = toFraction(rounded);

            if (fraction) {
                displayText = formatFraction(fraction);
                continuation = fraction.negative
                    ? `-(${fraction.numerator}/${fraction.denominator})`
                    : `(${fraction.numerator}/${fraction.denominator})`;
            }
        }

        if (displayText === undefined) {
            displayText = String(rounded);
            continuation = String(rounded);
        }

        lastResultValue = rounded;
        lastResultDisplay = displayText;
        expression = continuation;

        outputEl.textContent = displayText;
        askBtn.disabled = false;
    }

    function handleEquals() {
        if (!mathEngine || !expression) {
            return;
        }

        const originalExpression = expression;
        const result = evaluateExpression(mathEngine, expression, angleMode);

        if (!result.ok) {
            errorEl.textContent = result.message || 'Check your expression';
            return;
        }

        applyResult(roundDisplay(result.value));
        expressionEl.textContent = `${originalExpression} =`;
        lastExpressionText = originalExpression;
        errorEl.textContent = '';
        justEvaluated = true;
    }

    function toggleAngleMode() {
        angleMode = angleMode === 'deg' ? 'rad' : 'deg';
        angleBtn.textContent = angleMode;
        angleBtn.setAttribute('aria-pressed', String(angleMode === 'deg'));
        updateLiveOutput();
    }

    function toggleDisplayMode() {
        displayMode = displayMode === 'fraction' ? 'decimal' : 'fraction';
        formatBtn.classList.toggle('active', displayMode === 'fraction');
        formatBtn.setAttribute('aria-pressed', String(displayMode === 'fraction'));

        if (justEvaluated && lastResultValue !== null) {
            applyResult(lastResultValue);
        } else {
            updateLiveOutput();
        }
    }

    function handleKey({ insert, action }) {
        errorEl.textContent = '';

        if (insert !== undefined) {
            const isOperator = ['+', '-', '*', '/', '^'].includes(insert);

            if (justEvaluated && !isOperator) {
                expression = '';
            }

            expression += insert;
            justEvaluated = false;
        } else if (action === 'clear') {
            expression = '';
            justEvaluated = false;
            lastResultValue = null;
            lastResultDisplay = null;
            lastExpressionText = null;
            askBtn.disabled = true;
        } else if (action === 'backspace') {
            expression = expression.slice(0, -1);
            justEvaluated = false;
        } else if (action === 'negate') {
            expression = expression.startsWith('-(') && expression.endsWith(')')
                ? expression.slice(2, -1)
                : `-(${expression})`;
            justEvaluated = false;
        } else if (action === 'angle') {
            toggleAngleMode();
            return;
        } else if (action === 'frac') {
            toggleDisplayMode();
            return;
        } else if (action === 'equals') {
            handleEquals();
            return;
        }

        renderExpressionLine();
        updateLiveOutput();
    }

    keypad?.addEventListener('click', (event) => {
        const button = event.target.closest('button');

        if (!button) {
            return;
        }

        handleKey(button.dataset);
    });

    panelCalculator?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            handleKey({ action: 'equals' });
            return;
        }

        if (event.key === 'Backspace') {
            event.preventDefault();
            handleKey({ action: 'backspace' });
            return;
        }

        if (KEYBOARD_INSERT_PATTERN.test(event.key)) {
            event.preventDefault();
            handleKey({ insert: event.key });
        }
    });

    askBtn?.addEventListener('click', () => {
        if (lastResultValue === null || !lastExpressionText) {
            return;
        }

        const chatInput = document.getElementById('ai-input');

        if (!chatInput) {
            return;
        }

        chatInput.value = `Explain how ${lastExpressionText} = ${lastResultDisplay}`;

        activateTab(tabChat);
        chatInput.focus();
    });

    // ============================================
    // DRAGGABLE PANEL (Pointer Events — works for mouse, touch, pen)
    // ============================================

    let dragState = null;

    function isMobile() {
        return window.innerWidth < MOBILE_BREAKPOINT;
    }

    function clampToViewport(x, y) {
        const rect = panel.getBoundingClientRect();
        const maxX = Math.max(window.innerWidth - rect.width - CLAMP_MARGIN, CLAMP_MARGIN);
        const maxY = Math.max(window.innerHeight - rect.height - CLAMP_MARGIN, CLAMP_MARGIN);

        return {
            x: Math.min(Math.max(x, CLAMP_MARGIN), maxX),
            y: Math.min(Math.max(y, CLAMP_MARGIN), maxY),
        };
    }

    function applyPosition(x, y) {
        panel.style.left = `${x}px`;
        panel.style.top = `${y}px`;
        panel.style.right = 'auto';
        panel.style.bottom = 'auto';
    }

    function savePosition(x, y) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ x, y }));
        } catch {
            // Private browsing / quota exceeded — position just won't persist.
        }
    }

    function restorePosition() {
        if (isMobile()) {
            return;
        }

        let saved;

        try {
            saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
        } catch {
            saved = null;
        }

        if (!saved || typeof saved.x !== 'number' || typeof saved.y !== 'number') {
            return;
        }

        // Re-clamp against the CURRENT viewport before trusting a saved
        // position — it may have been saved on a much larger screen.
        const rect = panel.getBoundingClientRect();
        const fits = saved.x >= 0 && saved.y >= 0
            && saved.x + rect.width <= window.innerWidth
            && saved.y + rect.height <= window.innerHeight;

        if (fits) {
            applyPosition(saved.x, saved.y);
        }
    }

    function clearInlinePosition() {
        panel.style.left = '';
        panel.style.top = '';
        panel.style.right = '';
        panel.style.bottom = '';
    }

    function resetPosition() {
        clearInlinePosition();

        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch {
            // ignore
        }
    }

    resetBtn?.addEventListener('click', resetPosition);

    dragHandle?.addEventListener('pointerdown', (event) => {
        if (isMobile() || event.target.closest('button')) {
            return;
        }

        const rect = panel.getBoundingClientRect();

        dragState = {
            pointerId: event.pointerId,
            offsetX: event.clientX - rect.left,
            offsetY: event.clientY - rect.top,
        };

        // Without this, a fast drag can move the pointer off the handle
        // element entirely and silently lose the gesture mid-drag.
        dragHandle.setPointerCapture(event.pointerId);
        dragHandle.classList.add('dragging');
    });

    dragHandle?.addEventListener('pointermove', (event) => {
        if (!dragState || event.pointerId !== dragState.pointerId) {
            return;
        }

        const { x, y } = clampToViewport(
            event.clientX - dragState.offsetX,
            event.clientY - dragState.offsetY
        );

        applyPosition(x, y);
    });

    function endDrag(event) {
        if (!dragState || event.pointerId !== dragState.pointerId) {
            return;
        }

        dragHandle.classList.remove('dragging');

        const rect = panel.getBoundingClientRect();
        savePosition(rect.left, rect.top);

        dragState = null;
    }

    dragHandle?.addEventListener('pointerup', endDrag);
    dragHandle?.addEventListener('pointercancel', endDrag);

    window.addEventListener('resize', () => {
        if (isMobile()) {
            // Only drop the inline desktop-drag position for this viewport —
            // the saved value stays in localStorage in case the window
            // widens back out (e.g. a resize, not a permanent device switch).
            clearInlinePosition();
        }
    });

    restorePosition();

});

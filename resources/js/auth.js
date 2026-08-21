/* ================================
   resources/js/auth.js
   Shared behavior for the auth pages (sign-in, sign-up, reset-password).
   ================================ */

const EYE_OPEN = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
const EYE_OFF = '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';

/** Toggles the password field this button sits next to (x-input's built-in
 *  trailing toggle button — see resources/views/components/input.blade.php). */
function togglePasswordField(btn) {
    const input = btn.previousElementSibling;
    const svg = btn.querySelector('svg');
    const showing = input.type === 'password';

    input.type = showing ? 'text' : 'password';
    btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
    btn.setAttribute('aria-pressed', showing ? 'true' : 'false');
    svg.innerHTML = showing ? EYE_OFF : EYE_OPEN;
}

window.togglePasswordField = togglePasswordField;

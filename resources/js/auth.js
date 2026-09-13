/* ================================
   resources/js/auth.js
   Shared behavior for the auth pages (sign-in, sign-up, reset-password).
   ================================ */

// Password-field show/hide toggling lives inline in
// resources/views/components/input.blade.php (a @once-guarded delegated
// listener), since that component also renders on dashboard pages that
// don't load this file.

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets real HTTP security response headers app-wide. This app previously
 * only had X-Frame-Options/X-Content-Type-Options as <meta http-equiv>
 * tags on two Blade views, which browsers ignore for those headers
 * entirely — they were never actually enforced.
 *
 * This is also the sole owner of the Content-Security-Policy header (moved
 * here from docker/Caddyfile): the script-src nonce is only knowable inside
 * the PHP request lifecycle, since it has to match the nonce Vite::cspNonce()
 * embeds in every @vite(...)-rendered <script>/<link> tag for this same
 * request. Having two places (Caddy + Laravel) each try to set this header
 * would just create ambiguity about which one wins.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Must run before the view renders, so every @vite(...) tag in this
        // response is stamped with the same nonce we put in the header below.
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy(Vite::cspNonce()));

        return $response;
    }

    /**
     * Phase D of the CSP refactor: every inline handler in the app has been
     * converted to addEventListener wiring and every inline <script> block
     * carries this same nonce, so script-src can finally drop
     * 'unsafe-inline' and require the nonce instead.
     *
     * Deliberately NOT using 'strict-dynamic' here: every external script in
     * the app — the static MathJax/pdf.js <script src> tags, and the
     * jsPDF/XLSX libraries loadExportLibs() in teacher_dashboard.js/
     * admin_dashboard.js inserts via document.createElement — already loads
     * from cdn.jsdelivr.net or cdnjs.cloudflare.com, both already in this
     * allowlist. Adding 'strict-dynamic' would actively break the two static
     * tags: per spec, the instant 'strict-dynamic' is present, nonce-aware
     * browsers ignore host-source entries entirely (trusting only nonce'd or
     * dynamically-inserted-by-a-trusted-script sources), and neither static
     * tag carries a nonce.
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        return "default-src 'self'; ".
            "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; ".
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; ".
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; ".
            "img-src 'self' data: blob:; ".
            "connect-src 'self' https://*.supabase.co https://openrouter.ai https://generativelanguage.googleapis.com; ".
            "worker-src 'self' blob: https://cdnjs.cloudflare.com; ".
            "frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
    }
}

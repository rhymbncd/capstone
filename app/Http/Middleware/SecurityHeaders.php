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
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        return $response;
    }

    /**
     * IMPORTANT: script-src deliberately does NOT include the nonce source
     * yet, even though Vite::useCspNonce() above is already stamping one onto
     * every @vite(...) tag. Per the CSP spec, the instant a `nonce-` source
     * appears in script-src, nonce-aware browsers stop honoring
     * 'unsafe-inline' entirely (for the whole page, not just nonced tags) —
     * which would break every onclick="..." attribute still in the app
     * today, all at once. This policy is otherwise identical to the one
     * previously set in docker/Caddyfile (moved here so it can eventually
     * read the nonce). Add 'nonce-{$nonce}' to script-src (and drop
     * 'unsafe-inline') only once every inline handler in the app has been
     * converted to addEventListener wiring — see the CSP refactor plan.
     */
    private function contentSecurityPolicy(): string
    {
        return "default-src 'self'; ".
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; ".
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; ".
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; ".
            "img-src 'self' data: blob:; ".
            "connect-src 'self' https://*.supabase.co https://openrouter.ai https://generativelanguage.googleapis.com; ".
            "worker-src 'self' blob: https://cdnjs.cloudflare.com; ".
            "frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
    }
}

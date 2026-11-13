<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        // Questo host serve solo API e pannello admin: il sito pubblico e' midalot.live.
        // Google non deve indicizzare nulla di qui (compariva api.midalot.live nei risultati).
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->remove('X-Powered-By');

        if (!headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers and a Content Security Policy that only allows
 * the approved video, payment and font providers.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Per-request nonce for the few inline scripts Vite emits (asset prefetching).
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Filament's admin UI relies on inline scripts/styles; apply the strict CSP to the public site only.
        if (! $request->is('admin', 'admin/*', 'livewire*') && ! $response->headers->has('Content-Security-Policy')) {
            $frames = array_merge(
                ['https://www.youtube-nocookie.com', 'https://www.youtube.com', 'https://player.vimeo.com', 'https://vimeo.com', 'https://www.google.com', 'https://maps.google.com'],
                array_map(fn ($h) => 'https://'.$h, config('fco.streaming.embed_hosts', [])),
            );
            $vite = app()->environment('local') ? ' http://localhost:5173 ws://localhost:5173' : '';
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}' ".config('fco.csp_extra_script_src').$vite,
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com".$vite,
                "font-src 'self' https://fonts.gstatic.com data:",
                "img-src 'self' data: https://i.ytimg.com https://img.youtube.com https://i.vimeocdn.com",
                'media-src \'self\'',
                'frame-src '.implode(' ', array_unique($frames)),
                "connect-src 'self' ".config('fco.csp_extra_connect_src').$vite,
                "form-action 'self' https://checkout.paystack.com https://checkout.flutterwave.com",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "object-src 'none'",
            ]));
        }

        return $response;
    }
}

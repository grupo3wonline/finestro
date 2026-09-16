<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Security headers to protect against common web attacks
     * (clickjacking, MIME sniffing, XSS, mixed content, etc.).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (restrict browser features)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');

        // Content Security Policy — allowlist of the origins the app actually uses.
        // Skipped for non-HTML responses (files, downloads) to avoid overhead.
        if ($this->shouldApplyCsp($response)) {
            $response->headers->set('Content-Security-Policy', $this->buildContentSecurityPolicy());
        }

        // Cross-origin isolation hardening
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // Strict Transport Security (HTTPS only in production)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Remove server identification headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    /**
     * Only apply the CSP to HTML documents. Streamed/binary responses
     * (exports, images) should not carry a document CSP.
     */
    private function shouldApplyCsp(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return $contentType === '' || str_contains($contentType, 'text/html');
    }

    /**
     * Build the Content-Security-Policy directive string.
     *
     * The allowlist covers first-party assets plus the third parties the
     * app loads: Font Awesome (cdnjs), SweetAlert2 (jsdelivr), Google Fonts,
     * Google Tag Manager / Analytics and the Meta (Facebook) Pixel.
     */
    private function buildContentSecurityPolicy(): string
    {
        $scriptSrc = [
            "'self'",
            "'unsafe-inline'", // required by GTM/GA inline bootstrap and Blade inline scripts
            "'unsafe-eval'",   // required by Alpine.js standard build (evaluates x-* expressions via Function())
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://www.googletagmanager.com',
            'https://www.google-analytics.com',
            'https://connect.facebook.net',
        ];

        $styleSrc = [
            "'self'",
            "'unsafe-inline'", // Tailwind utility toggles and inline styles
            'https://cdnjs.cloudflare.com',
            'https://fonts.googleapis.com',
        ];

        $fontSrc = [
            "'self'",
            'https://fonts.gstatic.com',
            'https://cdnjs.cloudflare.com',
            'data:',
        ];

        $imgSrc = [
            "'self'",
            'data:',
            'https:',
        ];

        $connectSrc = [
            "'self'",
            'https://www.google-analytics.com',
            'https://www.googletagmanager.com',
            'https://connect.facebook.net',
            'https://api.stripe.com',
        ];

        $frameSrc = [
            "'self'",
            'https://www.googletagmanager.com',
            'https://js.stripe.com',
        ];

        $directives = [
            "default-src 'self'",
            'script-src ' . implode(' ', $scriptSrc),
            'style-src ' . implode(' ', $styleSrc),
            'font-src ' . implode(' ', $fontSrc),
            'img-src ' . implode(' ', $imgSrc),
            'connect-src ' . implode(' ', $connectSrc),
            'frame-src ' . implode(' ', $frameSrc),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        // Upgrade insecure requests only in production (https).
        if (app()->environment('production')) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevent site from being displayed in an iframe (Clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent browser from guessing content types (MIME Sniffing)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

$csp = "default-src 'self'; " .
    // Combined Local Vite and Tailscale Production (Added http Tailscale URL)
    "script-src 'self' 'unsafe-inline' https://9mint.tail511f6d.ts.net http://9mint.tail511f6d.ts.net http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173; " .
    
    // Combined Styles (Added http Tailscale URL)
    "style-src 'self' 'unsafe-inline' https://9mint.tail511f6d.ts.net http://9mint.tail511f6d.ts.net http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173; " .
    
    // Added http Tailscale URL to img-src
    "img-src 'self' data: https: http://9mint.tail511f6d.ts.net; " .
    
    "font-src 'self'; " .
    
    // Combined Connections (Added http Tailscale URL)
    "connect-src 'self' https://9mint.tail511f6d.ts.net http://9mint.tail511f6d.ts.net ws://localhost:5173 ws://127.0.0.1:5173 ws://[::1]:5173 http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173 http://127.0.0.1:8000;";
        $response->headers->set('Content-Security-Policy', $csp);

        // Hide the PHP version to prevent automated vulnerability scanning
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}

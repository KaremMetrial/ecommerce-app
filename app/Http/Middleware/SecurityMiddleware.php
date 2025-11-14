<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Add security headers
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Force HTTPS in production
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'";
        $response->headers->set('Content-Security-Policy', $csp);

        // Remove server information
        $response->headers->set('Server', '');
        $response->headers->remove('X-Powered-By');

        // Log suspicious requests
        $this->logSuspiciousRequest($request);

        return $response;
    }

    /**
     * Log suspicious requests
     */
    private function logSuspiciousRequest(Request $request): void
    {
        $suspiciousPatterns = [
            'union' => '/union\s+select/i',
            'sql_injection' => '/(\b(select|insert|update|delete|drop|create|alter|exec|execute)\b)/i',
            'xss' => '/<script[^>]*>.*?<\/script>/i',
            'path_traversal' => '/\.\.[\/\\\\]/i',
            'command_injection' => '/[;&|`$]/i',
        ];

        $url = $request->fullUrl();
        $userAgent = $request->userAgent();
        $ip = $request->ip();

        foreach ($suspiciousPatterns as $type => $pattern) {
            if (preg_match($pattern, $url) ||
                preg_match($pattern, $userAgent) ||
                preg_match($pattern, json_encode($request->all()))) {

                Log::warning('Suspicious request detected', [
                    'type' => $type,
                    'pattern' => $pattern,
                    'url' => $url,
                    'user_agent' => $userAgent,
                    'ip' => $ip,
                    'request_data' => $request->except(['password', 'token', 'secret']),
                    'timestamp' => now()->toISOString(),
                ]);

                // Apply rate limiting for suspicious requests
                $this->applyRateLimit($ip, 'suspicious');
                break;
            }
        }
    }

    /**
     * Apply rate limiting
     */
    private function applyRateLimit(string $ip, string $key): void
    {
        $key = "rate_limit:{$key}:{$ip}";
        $maxAttempts = 5;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $ip,
                'key' => $key,
                'attempts' => RateLimiter::attempts($key),
                'max_attempts' => $maxAttempts,
                'decay_minutes' => $decayMinutes,
            ]);
        }
    }

    /**
     * Validate request size
     */
    public static function validateRequestSize(Request $request): bool
    {
        $maxSize = 10 * 1024 * 1024; // 10MB
        $contentLength = strlen($request->getContent());

        if ($contentLength > $maxSize) {
            Log::warning('Request size exceeded', [
                'size' => $contentLength,
                'max_size' => $maxSize,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Sanitize input
     */
    public static function sanitizeInput(array $input): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeInput($value);
            } else {
                $sanitized[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            }
        }

        return $sanitized;
    }

    /**
     * Check for common attack patterns
     */
    public static function hasAttackPatterns(string $input): bool
    {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>))[^>]*>/i',
            '/<iframe\b[^<]*(?:(?!<\/iframe>))[^>]*>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<object\b[^<]*(?:(?!<\/object>))[^>]*>/i',
            '/<embed\b[^<]*(?:(?!<\/embed>))[^>]*>/i',
            '/<applet\b[^<]*(?:(?!<\/applet>))[^>]*>/i',
            '/<meta\b[^<]*(?:(?!<\/meta>))[^>]*>/i',
            '/<link\b[^<]*(?:(?!<\/link>))[^>]*>/i',
            '/<style\b[^<]*(?:(?!<\/style>))[^>]*>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }
}

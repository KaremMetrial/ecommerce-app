<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class SecurityHandler extends AbstractProcessingHandler
{
    /**
     * Handle security-related log records.
     */
    protected function write(array $record): void
    {
        // Check if this is a security event
        if ($this->isSecurityEvent($record)) {
            $this->handleSecurityEvent($record);
        }

        parent::write($record);
    }

    /**
     * Check if log record is a security event.
     */
    private function isSecurityEvent(array $record): bool
    {
        $securityKeywords = [
            'authentication',
            'authorization',
            'permission',
            'forbidden',
            'unauthorized',
            'security',
            'attack',
            'intrusion',
            'breach',
            'vulnerability',
            'malicious',
            'suspicious',
        ];

        $message = strtolower($record['message'] ?? '');
        $context = $record['context'] ?? [];

        // Check message for security keywords
        foreach ($securityKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        // Check context for security indicators
        if (isset($context['security_level']) ||
            isset($context['threat_level']) ||
            isset($context['attack_vector'])) {
            return true;
        }

        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/union.*select/i',
            '/script.*alert/i',
            '/drop.*table/i',
            '/insert.*into/i',
            '/update.*set/i',
            '/delete.*from/i',
            '/exec.*\(/i',
            '/system\(/i',
            '/cmd\.exe/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle security event.
     */
    private function handleSecurityEvent(array $record): void
    {
        $context = $record['context'] ?? [];
        $level = $record['level'] ?? 'info';

        // Add security metadata
        $record['context']['security'] = [
            'event_type' => $this->classifySecurityEvent($record),
            'severity' => $this->getSeverityLevel($level),
            'timestamp' => date('c'),
            'source_ip' => $context['ip'] ?? 'unknown',
            'user_agent' => $context['user_agent'] ?? 'unknown',
            'user_id' => $context['user_id'] ?? null,
            'session_id' => $context['session_id'] ?? null,
        ];

        // Send immediate alert for critical security events
        if (in_array($level, ['critical', 'alert', 'emergency'])) {
            $this->sendSecurityAlert($record);
        }

        // Log to separate security file
        $this->logToSecurityFile($record);
    }

    /**
     * Classify security event type.
     */
    private function classifySecurityEvent(array $record): string
    {
        $message = strtolower($record['message'] ?? '');

        if (str_contains($message, 'login') || str_contains($message, 'authentication')) {
            return 'authentication';
        }

        if (str_contains($message, 'permission') || str_contains($message, 'authorization')) {
            return 'authorization';
        }

        if (str_contains($message, 'forbidden') || str_contains($message, 'unauthorized')) {
            return 'access_denied';
        }

        if (str_contains($message, 'attack') || str_contains($message, 'intrusion')) {
            return 'attack_detected';
        }

        if (str_contains($message, 'sql') || str_contains($message, 'injection')) {
            return 'sql_injection';
        }

        if (str_contains($message, 'xss') || str_contains($message, 'script')) {
            return 'xss_attempt';
        }

        return 'security_event';
    }

    /**
     * Get severity level.
     */
    private function getSeverityLevel(string $level): string
    {
        $severityMap = [
            Logger::DEBUG => 'low',
            Logger::INFO => 'low',
            Logger::NOTICE => 'medium',
            Logger::WARNING => 'medium',
            Logger::ERROR => 'high',
            Logger::CRITICAL => 'critical',
            Logger::ALERT => 'critical',
            Logger::EMERGENCY => 'critical',
        ];

        return $severityMap[$level] ?? 'unknown';
    }

    /**
     * Send security alert.
     */
    private function sendSecurityAlert(array $record): void
    {
        $context = $record['context'] ?? [];
        $message = $record['message'] ?? 'Security event detected';

        // Send to Slack if configured
        if (config('services.slack.webhook_url')) {
            $this->sendSlackAlert($message, $context);
        }

        // Send email to admin
        if (config('app.admin_email')) {
            $this->sendEmailAlert($message, $context);
        }

        // Send to Sentry if configured
        if (config('services.sentry.dsn')) {
            $this->sendSentryAlert($record);
        }
    }

    /**
     * Send Slack alert.
     */
    private function sendSlackAlert(string $message, array $context): void
    {
        $webhookUrl = config('services.slack.webhook_url');
        $channel = config('services.slack.channel', '#security');

        $payload = [
            'text' => ':warning: *Security Alert*',
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        [
                            'title' => 'Event',
                            'value' => $context['security']['event_type'] ?? 'Unknown',
                            'short' => true,
                        ],
                        [
                            'title' => 'Message',
                            'value' => $message,
                            'short' => false,
                        ],
                        [
                            'title' => 'Severity',
                            'value' => $context['security']['severity'] ?? 'Unknown',
                            'short' => true,
                        ],
                        [
                            'title' => 'Source IP',
                            'value' => $context['security']['source_ip'] ?? 'Unknown',
                            'short' => true,
                        ],
                        [
                            'title' => 'User ID',
                            'value' => $context['security']['user_id'] ?? 'N/A',
                            'short' => true,
                        ],
                        [
                            'title' => 'Timestamp',
                            'value' => $context['security']['timestamp'] ?? now()->toDateTimeString(),
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];

        \Http::post($webhookUrl, $payload);
    }

    /**
     * Send email alert.
     */
    private function sendEmailAlert(string $message, array $context): void
    {
        $adminEmail = config('app.admin_email');

        if ($adminEmail) {
            \Mail::to($adminEmail)
                ->send(new \App\Mail\SecurityAlertMail($message, $context));
        }
    }

    /**
     * Send Sentry alert.
     */
    private function sendSentryAlert(array $record): void
    {
        // Sentry automatically captures the error, this is just a placeholder
        // In a real implementation, you might add additional context
        // or tags to the Sentry event
    }

    /**
     * Log to security file.
     */
    private function logToSecurityFile(array $record): void
    {
        $securityLog = storage_path('logs/security.log');
        $logEntry = sprintf(
            "[%s] %s: %s %s\n",
            $record['datetime'],
            strtoupper($record['level_name']),
            $record['message'],
            json_encode($record['context']['security'] ?? [])
        );

        file_put_contents($securityLog, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

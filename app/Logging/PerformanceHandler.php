<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class PerformanceHandler extends AbstractProcessingHandler
{
    /**
     * Handle performance log records.
     */
    protected function write(array $record): void
    {
        // Only process performance logs
        if (!isset($record['context']['performance'])) {
            return;
        }

        $performance = $record['context']['performance'];
        $thresholds = [
            'execution_time_ms' => 1000, // 1 second
            'memory_usage_mb' => 128,    // 128MB
            'memory_peak_mb' => 256,   // 256MB
        ];

        $alerts = [];

        // Check for performance issues
        foreach ($thresholds as $metric => $threshold) {
            if (isset($performance[$metric]) && $performance[$metric] > $threshold) {
                $alerts[] = [
                    'metric' => $metric,
                    'value' => $performance[$metric],
                    'threshold' => $threshold,
                    'severity' => $this->getAlertSeverity($metric, $performance[$metric], $threshold),
                ];
            }
        }

        // Log performance data
        $this->logPerformance($record, $performance);

        // Send alerts if any
        if (!empty($alerts)) {
            $this->sendPerformanceAlerts($record, $alerts);
        }
    }

    /**
     * Log performance data.
     */
    private function logPerformance(array $record, array $performance): void
    {
        $performanceLog = sprintf(
            "[%s] %s: %s | Execution: %sms | Memory: %sMB (Peak: %sMB)\n",
            $record['datetime'],
            strtoupper($record['level_name']),
            $record['message'],
            $performance['execution_time_ms'] ?? 'N/A',
            $performance['memory_usage_mb'] ?? 'N/A',
            $performance['memory_peak_mb'] ?? 'N/A'
        );

        $performanceLogFile = storage_path('logs/performance.log');
        file_put_contents($performanceLogFile, $performanceLog, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get alert severity.
     */
    private function getAlertSeverity(string $metric, float $value, float $threshold): string
    {
        $ratio = $value / $threshold;

        return match (true) {
            $ratio > 2.0 => 'critical',
            $ratio > 1.5 => 'high',
            $ratio > 1.0 => 'medium',
            default => 'low',
        };
    }

    /**
     * Send performance alerts.
     */
    private function sendPerformanceAlerts(array $record, array $alerts): void
    {
        foreach ($alerts as $alert) {
            $this->sendAlert($record, $alert);
        }
    }

    /**
     * Send individual alert.
     */
    private function sendAlert(array $record, array $alert): void
    {
        $message = sprintf(
            "Performance Alert: %s exceeded threshold (%s > %s)",
            $alert['metric'],
            $alert['value'],
            $alert['threshold']
        );

        $context = array_merge($record['context'] ?? [], [
            'alert' => $alert,
            'timestamp' => now()->toISOString(),
        ]);

        // Create alert record
        $alertRecord = [
            'message' => $message,
            'context' => $context,
            'level' => Logger::WARNING,
            'datetime' => $record['datetime'],
        ];

        // Send to monitoring services
        $this->sendToSlack($alertRecord);
        $this->sendToSentry($alertRecord);
    }

    /**
     * Send alert to Slack.
     */
    private function sendToSlack(array $record): void
    {
        $webhookUrl = config('services.slack.webhook_url');
        if (!$webhookUrl) {
            return;
        }

        $payload = [
            'text' => ':warning: Performance Alert',
            'attachments' => [
                [
                    'color' => 'warning',
                    'fields' => [
                        [
                            'title' => 'Metric',
                            'value' => $record['context']['alert']['metric'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Value',
                            'value' => $record['context']['alert']['value'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Threshold',
                            'value' => $record['context']['alert']['threshold'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Severity',
                            'value' => $record['context']['alert']['severity'],
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];

        \Http::post($webhookUrl, $payload);
    }

    /**
     * Send alert to Sentry.
     */
    private function sendToSentry(array $record): void
    {
        // Sentry automatically captures the record, this is just a placeholder
        // In a real implementation, you might add additional context
        // or tags to the Sentry event
    }
}

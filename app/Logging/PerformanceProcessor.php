<?php

namespace App\Logging;

use Monolog\LogRecord;

class PerformanceProcessor
{
    private static $startTime;
    private static $memoryUsage;

    /**
     * Initialize performance tracking.
     */
    public static function start(): void
    {
        self::$startTime = microtime(true);
        self::$memoryUsage = memory_get_usage(true);
    }

    /**
     * Process log record with performance data.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        if (self::$startTime) {
            $endTime = microtime(true);
            $executionTime = ($endTime - self::$startTime) * 1000; // Convert to milliseconds

            $currentMemory = memory_get_usage(true);
            $memoryUsed = $currentMemory['real'] - self::$memoryUsage['real'];

            $record->extra['performance'] = [
                'execution_time_ms' => round($executionTime, 2),
                'memory_used_bytes' => $memoryUsed,
                'memory_peak_bytes' => memory_get_peak_usage(true)['real'],
                'memory_usage_mb' => round($memoryUsed / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true)['real'] / 1024 / 1024, 2),
            ];
        }

        return $record;
    }

    /**
     * Reset performance tracking.
     */
    public static function reset(): void
    {
        self::$startTime = null;
        self::$memoryUsage = null;
    }
}

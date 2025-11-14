<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default channel that gets used when writing
    | messages to the logs. The value specified here should match
    | one of the channels defined in the "channels" configuration array.
    |
    */
    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the deprecation log channel that gets used when
    | writing deprecation warnings. The value specified here should match
    | one of the channels defined in the "channels" configuration array.
    |
    */
    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog library which provides a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    | "errorlog", "monolog",
    |
    */
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'performance', 'security'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'replace_placeholders' => true,
            'bubble' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'days' => 14,
            'replace_placeholders' => true,
            'bubble' => true,
        ],

        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'queue' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queue.log'),
            'level' => 'info',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'payment' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payment.log'),
            'level' => 'info',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        'database' => [
            'driver' => 'daily',
            'path' => storage_path('logs/database.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'cache' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cache.log'),
            'level' => 'info',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // Slack channel for critical alerts
        'slack' => [
            'driver' => 'slack',
            'url' => env('SLACK_WEBHOOK_URL'),
            'username' => env('SLACK_USERNAME', 'Laravel Bot'),
            'emoji' => true,
            'level' => 'critical',
            'replace_placeholders' => true,
        ],

        // Sentry channel for error tracking
        'sentry' => [
            'driver' => 'monolog',
            'handler' => Sentry\Laravel\Integration\SentryHandler::class,
            'level' => 'error',
            'bubble' => true,
        ],

        // Syslog for system integration
        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'warning'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        // Errorlog for Windows compatibility
        'errorlog' => [
            'driver' => 'errorlog',
            'path' => storage_path('logs/error.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'replace_placeholders' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Monolog Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure Monolog handlers and formatters for your
    | application. This provides more granular control over logging.
    |
    */
    'processors' => [
        // Add request ID to all log entries
        \App\Logging\RequestIdProcessor::class,

        // Add user context to log entries
        \App\Logging\UserContextProcessor::class,

        // Add performance timing
        \App\Logging\PerformanceProcessor::class,

        // Sanitize sensitive data
        \App\Logging\SanitizeProcessor::class,
    ],

    'handlers' => [
        // Custom handler for structured logging
        'json' => [
            'class' => \App\Logging\JsonHandler::class,
            'level' => 'debug',
            'bubble' => true,
        ],

        // Handler for performance monitoring
        'performance' => [
            'class' => \App\Logging\PerformanceHandler::class,
            'level' => 'info',
            'bubble' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tap Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the "taps" that will be executed when
    | a log message is logged. These taps can be used to send
    | log messages to external services, trigger notifications, etc.
    |
    */
    'tap' => [
        // Send critical logs to Slack
        \App\Logging\SlackTap::class,

        // Send errors to Sentry
        \App\Logging\SentryTap::class,

        // Send performance metrics to monitoring service
        \App\Logging\PerformanceTap::class,

        // Send security alerts to admin
        \App\Logging\SecurityTap::class,
    ],
];

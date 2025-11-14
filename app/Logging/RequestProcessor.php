<?php

namespace App\Logging;

use Monolog\LogRecord;

class RequestProcessor
{
    /**
     * Process the log record.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $request = request();

        if ($request) {
            $record->extra['request_id'] = $this->generateRequestId();
            $record->extra['ip'] = $request->ip();
            $record->extra['user_agent'] = $request->userAgent();
            $record->extra['url'] = $request->fullUrl();
            $record->extra['method'] = $request->method();

            // Add user context if authenticated
            if ($request->user()) {
                $record->extra['user_id'] = $request->user()->id;
                $record->extra['user_email'] = $request->user()->email;
            }

            // Add session ID
            $record->extra['session_id'] = $request->session()->getId();
        }

        return $record;
    }

    /**
     * Generate unique request ID.
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }
}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[Admin Alert] {{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .alert {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error-details {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 0.9em;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="alert">
        <h2>🚨 Admin Alert</h2>
        <p><strong>{{ $subject }}</strong></p>
    </div>

    <div class="content">
        <h3>Message</h3>
        <p>{{ $message }}</p>

        @if($hasException)
            <h3>Error Details</h3>
            <div class="error-details">
                <p><strong>Error Message:</strong> {{ $errorDetails['message'] }}</p>
                <p><strong>File:</strong> {{ $errorDetails['file'] }}</p>
                <p><strong>Line:</strong> {{ $errorDetails['line'] }}</p>
                <details>
                    <summary>Stack Trace</summary>
                    <pre style="white-space: pre-wrap; word-wrap: break-word;">{{ $errorDetails['trace'] }}</pre>
                </details>
            </div>
        @endif

        <div style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4 style="margin-top: 0;">📋 Recommended Actions</h4>
            <ul>
                <li>Review the error details above</li>
                <li>Check the application logs for more context</li>
                <li>Investigate the affected system components</li>
                <li>Take corrective action as needed</li>
                @if($hasException)
                    <li>Monitor for recurring issues</li>
                @endif
            </ul>
        </div>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}/admin"
           style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Go to Admin Dashboard
        </a>
    </div>

    <div class="footer">
        <p>This is an automated system notification from {{ config('app.name') }}.</p>
        <p>If you believe this is an error, please contact your system administrator.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>

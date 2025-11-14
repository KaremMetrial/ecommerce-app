<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code ?? 'Error' }} | {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .error-container {
            text-align: center;
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 20px;
        }
        .error-code {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            font-family: monospace;
        }
        .error-title {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #333;
        }
        .error-message {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #dee2e6;
        }
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        .error-details {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .timestamp {
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 15px;
        }
        @media (max-width: 600px) {
            .error-container {
                margin: 10px;
                padding: 40px 20px;
            }
            .error-code {
                font-size: 2rem;
            }
            .error-title {
                font-size: 1.5rem;
            }
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">{{ $code ?? 500 }}</div>
        <h1 class="error-title">{{ $message ?? 'Something went wrong' }}</h1>
        <p class="error-message">
            {{ $description ?? 'An unexpected error occurred. Please try again or contact support if the problem persists.' }}
        </p>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>

        @if($code || $message)
            <div class="error-details">
                <p><strong>Error Code:</strong> {{ $code ?? 'Unknown' }}</p>
                @if($message)
                    <p><strong>Message:</strong> {{ $message }}</p>
                @endif
                <p class="timestamp"><strong>Time:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
            </div>
        @endif
    </div>

    <script>
        // Add error reporting functionality
        document.addEventListener('DOMContentLoaded', function() {
            const errorData = {
                code: {{ $code ?? 'null' }},
                message: {{ $message ?? 'null' }},
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            };

            // Send error to monitoring service (if available)
            if (typeof window.errorReporting === 'function') {
                window.errorReporting(errorData);
            }

            // Add subtle animation to error code
            const errorCode = document.querySelector('.error-code');
            if (errorCode) {
                errorCode.style.animation = 'pulse 2s infinite';
            }
        });

        // Create pulse animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.05); opacity: 0.8; }
                100% { transform: scale(1); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

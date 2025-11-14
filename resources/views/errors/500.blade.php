<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
        .error-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: shake 0.5s;
        }
        .error-title {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #333;
        }
        .error-description {
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
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
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
        }
        .error-id {
            font-family: monospace;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .retry-section {
            margin-top: 30px;
            padding: 20px;
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 10px;
        }
        .retry-btn {
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .retry-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .countdown {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
            margin-top: 15px;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        @media (max-width: 600px) {
            .error-container {
                margin: 10px;
                padding: 40px 20px;
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
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">Something Went Wrong</h1>
        <p class="error-description">
            We're sorry, but something went wrong on our end. Our team has been notified and is working to fix the issue.
        </p>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>

        <div class="error-details">
            <h4>Error Details</h4>
            <p><strong>Error Code:</strong> <span class="error-id">500</span></p>
            <p><strong>Time:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
            <p><strong>Reference ID:</strong> <span class="error-id">{{ Str::random(8) }}</span></p>
        </div>

        <div class="retry-section">
            <h4>Try Again</h4>
            <p>You can try refreshing the page or come back in a few minutes.</p>
            <button class="retry-btn" onclick="window.location.reload()">
                Refresh Page
            </button>
            <div class="countdown" id="countdown">Auto-refresh in 10 seconds...</div>
        </div>
    </div>

    <script>
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');

        const updateCountdown = () => {
            if (countdown > 0) {
                countdownElement.textContent = `Auto-refresh in ${countdown} seconds...`;
                countdown--;
                setTimeout(updateCountdown, 1000);
            } else {
                countdownElement.textContent = 'Refreshing...';
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        };

        // Start countdown
        setTimeout(updateCountdown, 1000);

        // Report error to monitoring service (if available)
        if (typeof window.errorReporting === 'function') {
            window.errorReporting({
                type: 'server_error',
                code: 500,
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            });
        }
    </script>
</body>
</html>

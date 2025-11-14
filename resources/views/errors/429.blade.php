<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 - Too Many Requests | {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            color: #f5576c;
            animation: pulse 2s infinite;
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
        .rate-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
        }
        .rate-info h4 {
            margin-bottom: 15px;
            color: #333;
        }
        .rate-info ul {
            list-style: none;
        }
        .rate-info li {
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .rate-info li:last-child {
            border-bottom: none;
        }
        .countdown {
            font-size: 1.2rem;
            font-weight: bold;
            color: #f5576c;
            margin-top: 20px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
            border-radius: 4px;
            transition: width 1s ease;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
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
        <div class="error-icon">⏱️</div>
        <h1 class="error-title">Too Many Requests</h1>
        <p class="error-description">
            You've made too many requests in a short period. Please wait a moment before trying again.
        </p>

        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
        </div>

        <div class="countdown" id="countdown">Please wait 60 seconds...</div>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>

        <div class="rate-info">
            <h4>Rate Limits</h4>
            <ul>
                <li><strong>Authenticated Users:</strong> 60 requests per minute</li>
                <li><strong>Guest Users:</strong> 100 requests per minute</li>
                <li><strong>Burst Limit:</strong> 10 requests per second</li>
                <li><strong>Reset Time:</strong> Every minute</li>
            </ul>
        </div>
    </div>

    <script>
        let countdown = 60;
        const countdownElement = document.getElementById('countdown');
        const progressFill = document.getElementById('progressFill');

        const updateCountdown = () => {
            if (countdown > 0) {
                countdownElement.textContent = `Please wait ${countdown} seconds...`;
                progressFill.style.width = `${((60 - countdown) / 60) * 100}%`;
                countdown--;
                setTimeout(updateCountdown, 1000);
            } else {
                countdownElement.textContent = 'You can try again now!';
                progressFill.style.width = '100%';
                countdownElement.style.color = '#28a745';
            }
        };

        // Start countdown
        setTimeout(updateCountdown, 1000);

        // Auto-refresh when countdown reaches 0
        setTimeout(() => {
            if (countdown > 0) {
                window.location.reload();
            }
        }, 61000);

        // Log rate limit events for monitoring
        if (typeof window.rateLimitMonitor === 'function') {
            window.rateLimitMonitor({
                type: 'rate_limit_exceeded',
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            });
        }
    </script>
</body>
</html>

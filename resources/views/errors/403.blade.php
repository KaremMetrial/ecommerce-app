<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
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
            color: #dc3545;
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
        .permission-info {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
        }
        .permission-info h4 {
            margin-bottom: 15px;
            color: #721c24;
        }
        .permission-info ul {
            list-style: none;
        }
        .permission-info li {
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #f5c6cb;
        }
        .permission-info li:last-child {
            border-bottom: none;
        }
        .login-prompt {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        .login-btn {
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .login-btn:hover {
            background: #218838;
            transform: translateY(-2px);
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
        <div class="error-icon">🔒</div>
        <h1 class="error-title">Access Denied</h1>
        <p class="error-description">
            You don't have permission to access this resource. Please contact your administrator if you believe this is an error.
        </p>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>

        <div class="permission-info">
            <h4>Why am I seeing this?</h4>
            <ul>
                <li>You don't have the required role or permissions</li>
                <li>Your session may have expired</li>
                <li>You're trying to access admin-only resources</li>
                <li>The resource has been restricted to certain users</li>
            </ul>
        </div>

        @guest()
            <div class="login-prompt">
                <h4>Need to log in?</h4>
                <p>If you have an account, please log in to continue.</p>
                <a href="{{ route('login') }}" class="login-btn">Log In</a>
            </div>
        @endguest
    </div>

    <script>
        // Log access denied attempts for security monitoring
        document.addEventListener('DOMContentLoaded', function() {
            const accessData = {
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString(),
                referrer: document.referrer
            };

            // Send to security monitoring (if available)
            if (typeof window.securityMonitor === 'function') {
                window.securityMonitor('access_denied', accessData);
            }

            // Add subtle animation to lock icon
            const icon = document.querySelector('.error-icon');
            icon.style.animation = 'shake 0.5s';
        });
    </script>
</body>
</html>

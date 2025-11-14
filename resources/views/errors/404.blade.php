<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | {{ config('app.name') }}</title>
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
            font-size: 8rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
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
        .search-box {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .search-input {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            width: 250px;
            transition: border-color 0.3s ease;
        }
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        .search-btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        .search-btn:hover {
            background: #5a67d8;
        }
        .suggestions {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .suggestions h4 {
            margin-bottom: 15px;
            color: #333;
        }
        .suggestions ul {
            list-style: none;
            text-align: left;
        }
        .suggestions li {
            margin: 8px 0;
            padding: 8px 0;
        }
        .suggestions a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .suggestions a:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .error-container {
                margin: 10px;
                padding: 40px 20px;
            }
            .error-code {
                font-size: 6rem;
            }
            .error-title {
                font-size: 1.5rem;
            }
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
            .search-box {
                flex-direction: column;
                align-items: center;
            }
            .search-input {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-description">
            Oops! The page you're looking for doesn't exist. It might have been moved, deleted, or you entered the wrong URL.
        </p>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>

        <form class="search-box" action="{{ url('/search') }}" method="GET">
            <input type="text" name="q" class="search-input" placeholder="Search for products...">
            <button type="submit" class="search-btn">Search</button>
        </form>

        <div class="suggestions">
            <h4>Looking for something?</h4>
            <ul>
                <li><a href="{{ url('/products') }}">Browse Products</a></li>
                <li><a href="{{ url('/categories') }}">Shop by Category</a></li>
                <li><a href="{{ url('/contact') }}">Contact Support</a></li>
            </ul>
        </div>
    </div>

    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const errorCode = document.querySelector('.error-code');

            // Add a subtle animation
            errorCode.style.animation = 'pulse 2s infinite';

            // Create pulse animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                    100% { transform: scale(1); }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isCritical ? '🚨 Product Out of Stock' : '⚠️ Low Stock Alert' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .alert-critical {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .product-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .product-details {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .product-image {
            width: 80px;
            height: 80px;
            background: #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            color: #666;
        }
        .product-text {
            flex: 1;
        }
        .action-button {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
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
    <div class="{{ $isCritical ? 'alert-critical' : 'alert-warning' }}">
        <h2>{{ $isCritical ? '🚨 Product Out of Stock' : '⚠️ Low Stock Alert' }}</h2>
        <p>The following product has {{ $isCritical ? 'run out of stock' : 'low stock levels' }} and requires your attention.</p>
    </div>

    <div class="product-info">
        <h3>Product Details</h3>
        <div class="product-details">
            <div class="product-image">
                📦
            </div>
            <div class="product-text">
                <p><strong>Name:</strong> {{ $productName }}</p>
                <p><strong>SKU:</strong> {{ $productSku }}</p>
                <p><strong>Current Stock:</strong> {{ $stockQuantity }} units</p>
                <p><strong>Status:</strong> {{ $isCritical ? 'Out of Stock' : 'Low Stock' }}</p>
            </div>
        </div>
    </div>

    @if($isCritical)
        <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4 style="margin-top: 0;">⚠️ Immediate Action Required</h4>
            <p>This product is completely out of stock and cannot be purchased until inventory is replenished.</p>
            <ul>
                <li>Update inventory levels immediately</li>
                <li>Consider removing from featured listings</li>
                <li>Notify customers of backorder status if applicable</li>
            </ul>
        </div>
    @else
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4 style="margin-top: 0;">📋 Recommended Actions</h4>
            <ul>
                <li>Monitor stock levels closely</li>
                <li>Consider reordering soon</li>
                <li>Update product availability status if needed</li>
            </ul>
        </div>
    @endif

    <div style="text-align: center;">
        <a href="{{ $productUrl }}" class="action-button">
            View Product in Admin
        </a>
    </div>

    <div class="footer">
        <p>This is an automated notification from the {{ config('app.name') }} inventory system.</p>
        <p>If you believe this is an error, please contact your system administrator.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>

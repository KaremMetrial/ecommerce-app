<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation - Order #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #28a745;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .payment-info {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .order-details {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
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
    <div class="header">
        <h1>✓ Payment Confirmed</h1>
        <p>Your payment has been successfully processed</p>
    </div>

    <div class="content">
        <div class="payment-info">
            <h2>Payment Details</h2>
            <p><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $paymentMethod)) }}</p>
            <p><strong>Amount:</strong> ${{ number_format($amount, 2) }} {{ $currency }}</p>
            <p><strong>Transaction ID:</strong> {{ $transactionId }}</p>
            <p><strong>Payment Date:</strong> {{ $paidAt->format('F j, Y, g:i A') }}</p>
            <p><strong>Status:</strong> <span class="status-badge">Completed</span></p>
        </div>

        <div class="order-details">
            <h3>Order Information</h3>
            <p><strong>Order Number:</strong> #{{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y, g:i A') }}</p>
            <p><strong>Order Status:</strong> {{ ucfirst($order->status) }}</p>
            <p><strong>Order Total:</strong> ${{ number_format($order->total, 2) }} {{ $order->currency }}</p>
        </div>

        <div class="order-details">
            <h3>Customer Information</h3>
            <p><strong>Name:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
        </div>

        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4 style="color: #856404; margin-top: 0;">What's Next?</h4>
            <ul style="color: #856404;">
                <li>You will receive a separate email when your order ships</li>
                <li>You can track your order status in your account dashboard</li>
                <li>Save this email for your records</li>
                <li>Contact customer service if you have any questions</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('orders.show', $order->id) }}"
               style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                View Order Details
            </a>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for your purchase!</p>
        <p>If you have any questions about this payment, please contact our customer service.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>

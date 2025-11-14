<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #{{ $order->order_number }}</title>
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
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid #007bff;
        }
        .content {
            padding: 20px 0;
        }
        .order-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .address {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .totals {
            text-align: right;
            margin: 20px 0;
        }
        .totals div {
            margin: 5px 0;
        }
        .total-row {
            font-weight: bold;
            font-size: 1.2em;
            border-top: 2px solid #007bff;
            padding-top: 10px;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #ddd;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Confirmation</h1>
        <p>Thank you for your order!</p>
    </div>

    <div class="content">
        <div class="order-info">
            <h2>Order Information</h2>
            <p><strong>Order Number:</strong> #{{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y, g:i A') }}</p>
            <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
        </div>

        <h3>Order Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->product_sku }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td>${{ number_format($item->total_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><strong>Subtotal:</strong> ${{ number_format($subtotal, 2) }}</div>
            @if($taxAmount > 0)
                <div><strong>Tax:</strong> ${{ number_format($taxAmount, 2) }}</div>
            @endif
            @if($shippingAmount > 0)
                <div><strong>Shipping:</strong> ${{ number_format($shippingAmount, 2) }}</div>
            @endif
            @if($discountAmount > 0)
                <div><strong>Discount:</strong> -${{ number_format($discountAmount, 2) }}</div>
            @endif
            <div class="total-row"><strong>Total:</strong> ${{ number_format($total, 2) }}</div>
        </div>

        <div style="display: flex; gap: 20px;">
            <div class="address" style="flex: 1;">
                <h4>Shipping Address</h4>
                <p>
                    {{ $shippingAddress['first_name'] }} {{ $shippingAddress['last_name'] }}<br>
                    @if($shippingAddress['company']){{ $shippingAddress['company'] }}<br>@endif
                    {{ $shippingAddress['address_line_1'] }}<br>
                    @if($shippingAddress['address_line_2']){{ $shippingAddress['address_line_2'] }}<br>@endif
                    {{ $shippingAddress['city'] }}, {{ $shippingAddress['state'] }} {{ $shippingAddress['postal_code'] }}<br>
                    {{ $shippingAddress['country'] }}<br>
                    @if($shippingAddress['phone']){{ $shippingAddress['phone'] }}<br>@endif
                    {{ $shippingAddress['email'] }}
                </p>
            </div>

            @if($billingAddress)
                <div class="address" style="flex: 1;">
                    <h4>Billing Address</h4>
                    <p>
                        {{ $billingAddress['first_name'] }} {{ $billingAddress['last_name'] }}<br>
                        @if($billingAddress['company']){{ $billingAddress['company'] }}<br>@endif
                        {{ $billingAddress['address_line_1'] }}<br>
                        @if($billingAddress['address_line_2']){{ $billingAddress['address_line_2'] }}<br>@endif
                        {{ $billingAddress['city'] }}, {{ $billingAddress['state'] }} {{ $billingAddress['postal_code'] }}<br>
                        {{ $billingAddress['country'] }}<br>
                        @if($billingAddress['phone']){{ $billingAddress['phone'] }}<br>@endif
                        {{ $billingAddress['email'] }}
                    </p>
                </div>
            @endif
        </div>

        @if($order->notes)
            <div class="order-info">
                <h4>Order Notes</h4>
                <p>{{ $order->notes }}</p>
            </div>
        @endif
    </div>

    <div class="footer">
        <p>Thank you for shopping with us!</p>
        <p>If you have any questions about your order, please contact our customer service.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>

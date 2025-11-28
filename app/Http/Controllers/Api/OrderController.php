<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Address;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items.product', 'items.variant', 'payment'])
            ->when($request->input('status'), function ($query, $status) {
                $query->byStatus($status);
            })
            ->when($request->input('payment_status'), function ($query, $status) {
                $query->byPaymentStatus($status);
            })
            ->recent()
            ->paginate($request->input('per_page', 15));

        return ApiResponse::paginated($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== $request->user()->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $order->load(['items.product', 'items.variant', 'payment', 'user']);

        return ApiResponse::success(new OrderResource($order));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string|max:255',
            'shipping_address.last_name' => 'required|string|max:255',
            'shipping_address.company' => 'nullable|string|max:255',
            'shipping_address.address_line_1' => 'required|string|max:255',
            'shipping_address.address_line_2' => 'nullable|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.state' => 'required|string|max:255',
            'shipping_address.postal_code' => 'required|string|max:20',
            'shipping_address.country' => 'required|string|max:255',
            'shipping_address.phone' => 'nullable|string|max:20',
            'shipping_address.email' => 'nullable|email|max:255',
            'billing_address' => 'nullable|array',
            'billing_address.first_name' => 'required_with:billing_address|string|max:255',
            'billing_address.last_name' => 'required_with:billing_address|string|max:255',
            'billing_address.company' => 'nullable|string|max:255',
            'billing_address.address_line_1' => 'required_with:billing_address|string|max:255',
            'billing_address.address_line_2' => 'nullable|string|max:255',
            'billing_address.city' => 'required_with:billing_address|string|max:255',
            'billing_address.state' => 'required_with:billing_address|string|max:255',
            'billing_address.postal_code' => 'required_with:billing_address|string|max:20',
            'billing_address.country' => 'required_with:billing_address|string|max:255',
            'billing_address.phone' => 'nullable|string|max:20',
            'billing_address.email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|string|in:stripe,paypal,cod,bank_transfer',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $user = $request->user();
            $cart = $user->getCart();

            if ($cart->isEmpty()) {
                return ApiResponse::error('Cannot create order with empty cart', 422);
            }

            // Check stock availability
            foreach ($cart->items as $item) {
                if (!$item->is_available) {
                    return ApiResponse::error("Product '{$item->product_name}' is no longer available", 422);
                }
            }

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $cart->subtotal,
                'tax_amount' => $cart->tax_amount,
                'shipping_amount' => $cart->shipping_amount,
                'discount_amount' => $cart->discount_amount,
                'total' => $cart->total,
                'currency' => $cart->currency,
                'shipping_address' => $validated['shipping_address'],
                'billing_address' => $validated['billing_address'] ?? $validated['shipping_address'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create order items
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'variant_name' => $item->variant_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'product_data' => $item->product_data,
                ]);

                // Decrease stock
                if ($item->variant) {
                    $item->variant->decreaseStock($item->quantity);
                } else {
                    $item->product->decreaseStock($item->quantity);
                }
            }

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
                'amount' => $order->total,
                'currency' => $order->currency,
            ]);

            // Clear cart
            $cart->clear();

            // Load relationships for response
            $order->load(['items.product', 'items.variant', 'payment']);

            return ApiResponse::success([
                'message' => 'Order created successfully',
                'order' => new OrderResource($order),
            ], 201);
        });
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        // Ensure user can only cancel their own orders
        if ($order->user_id !== $request->user()->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        if (!$order->canBeCancelled()) {
            return ApiResponse::error('Order cannot be cancelled', 422);
        }

        $order->cancel();

        return ApiResponse::success([
            'message' => 'Order cancelled successfully',
            'order' => new OrderResource($order->load(['items.product', 'items.variant', 'payment'])),
        ]);
    }

    public function track(Request $request, Order $order): JsonResponse
    {
        // Ensure user can only track their own orders
        if ($order->user_id !== $request->user()->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        return ApiResponse::success([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $order->payment_status_label,
            'created_at' => $order->created_at,
            'shipped_at' => $order->shipped_at,
            'delivered_at' => $order->delivered_at,
            'tracking_info' => $this->getTrackingInfo($order),
        ]);
    }

    private function getTrackingInfo(Order $order): array
    {
        // This would integrate with shipping carriers
        // For now, return basic status information
        $tracking = [];

        switch ($order->status) {
            case 'pending':
                $tracking = [
                    'status' => 'Order Received',
                    'description' => 'Your order has been received and is being processed.',
                    'estimated_delivery' => null,
                ];
                break;
            case 'confirmed':
                $tracking = [
                    'status' => 'Order Confirmed',
                    'description' => 'Your order has been confirmed and is being prepared.',
                    'estimated_delivery' => now()->addDays(3)->format('Y-m-d'),
                ];
                break;
            case 'processing':
                $tracking = [
                    'status' => 'Processing',
                    'description' => 'Your order is being prepared for shipment.',
                    'estimated_delivery' => now()->addDays(2)->format('Y-m-d'),
                ];
                break;
            case 'shipped':
                $tracking = [
                    'status' => 'Shipped',
                    'description' => 'Your order has been shipped and is on its way.',
                    'estimated_delivery' => now()->addDays(1)->format('Y-m-d'),
                ];
                break;
            case 'delivered':
                $tracking = [
                    'status' => 'Delivered',
                    'description' => 'Your order has been delivered successfully.',
                    'estimated_delivery' => null,
                ];
                break;
            case 'cancelled':
                $tracking = [
                    'status' => 'Cancelled',
                    'description' => 'Your order has been cancelled.',
                    'estimated_delivery' => null,
                ];
                break;
            case 'refunded':
                $tracking = [
                    'status' => 'Refunded',
                    'description' => 'Your order has been refunded.',
                    'estimated_delivery' => null,
                ];
                break;
        }

        return $tracking;
    }
}

<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateProductStock implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Only update stock when order is confirmed or cancelled
        if (!in_array($event->newStatus, ['confirmed', 'cancelled'])) {
            return;
        }

        $order = $event->order;
        $isCancellation = $event->newStatus === 'cancelled';
        $wasPreviouslyConfirmed = in_array($event->oldStatus, ['confirmed', 'processing', 'shipped', 'delivered']);

        // If order is being cancelled and was previously confirmed, restore stock
        // If order is being confirmed, reduce stock
        if ($isCancellation && $wasPreviouslyConfirmed) {
            $this->restoreStock($order);
        } elseif ($event->newStatus === 'confirmed' && !$wasPreviouslyConfirmed) {
            $this->reduceStock($order);
        }
    }

    /**
     * Reduce product stock when order is confirmed.
     */
    private function reduceStock($order): void
    {
        try {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    $product = $item->product;
                    $variant = $item->productVariant;

                    if ($variant) {
                        // Update variant stock
                        $variant->decrement('stock_quantity', $item->quantity);

                        // Update product stock (sum of all variants)
                        $product->update([
                            'stock_quantity' => $product->variants()->sum('stock_quantity')
                        ]);
                    } else {
                        // Update product stock directly
                        $product->decrement('stock_quantity', $item->quantity);
                    }

                    // Check if product is out of stock
                    if ($product->stock_quantity <= 0) {
                        event(new \App\Events\ProductOutOfStock($product));
                    }

                    Log::info('Stock reduced for order item', [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'remaining_stock' => $product->stock_quantity,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to reduce stock', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Restore product stock when order is cancelled.
     */
    private function restoreStock($order): void
    {
        try {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    $product = $item->product;
                    $variant = $item->productVariant;

                    if ($variant) {
                        // Update variant stock
                        $variant->increment('stock_quantity', $item->quantity);

                        // Update product stock (sum of all variants)
                        $product->update([
                            'stock_quantity' => $product->variants()->sum('stock_quantity')
                        ]);
                    } else {
                        // Update product stock directly
                        $product->increment('stock_quantity', $item->quantity);
                    }

                    Log::info('Stock restored for cancelled order item', [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'remaining_stock' => $product->stock_quantity,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to restore stock', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderStatusChanged $event, \Throwable $exception): void
    {
        Log::error('Failed to update product stock', [
            'order_id' => $event->order->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'error' => $exception->getMessage(),
        ]);
    }
}

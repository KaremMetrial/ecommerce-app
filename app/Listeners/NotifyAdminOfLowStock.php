<?php

namespace App\Listeners;

use App\Events\ProductOutOfStock;
use App\Jobs\SendLowStockNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdminOfLowStock implements ShouldQueue
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
    public function handle(ProductOutOfStock $event): void
    {
        $product = $event->product;

        try {
            // Check if this is a critical low stock situation
            $isCritical = $product->stock_quantity <= 0;

            // Dispatch job to send notification to admin
            SendLowStockNotificationJob::dispatch($product, $isCritical);

            Log::info('Low stock notification job dispatched', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'stock_quantity' => $product->stock_quantity,
                'is_critical' => $isCritical,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch low stock notification job', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductOutOfStock $event, \Throwable $exception): void
    {
        Log::error('Failed to notify admin of low stock', [
            'product_id' => $event->product->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

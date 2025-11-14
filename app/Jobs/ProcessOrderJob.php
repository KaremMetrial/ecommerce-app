<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {
        $this->onQueue('orders');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing order', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status,
            ]);

            // Update order status to processing
            $this->order->update([
                'status' => 'processing',
                'processed_at' => now(),
            ]);

            // Fire order status changed event
            event(new \App\Events\OrderStatusChanged(
                $this->order,
                'pending',
                'processing'
            ));

            // Reserve inventory
            $this->reserveInventory();

            // Send order to fulfillment system
            $this->sendToFulfillmentSystem();

            Log::info('Order processed successfully', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process order', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark order as failed
            $this->order->update([
                'status' => 'failed',
                'failed_at' => now(),
            ]);

            // Retry job with exponential backoff
            $this->release(300); // 5 minutes delay
        }
    }

    /**
     * Reserve inventory for order items.
     */
    private function reserveInventory(): void
    {
        foreach ($this->order->items as $item) {
            $product = $item->product;
            $variant = $item->productVariant;

            if ($variant) {
                // Reserve variant inventory
                $variant->decrement('reserved_quantity', $item->quantity);
            } else {
                // Reserve product inventory
                $product->decrement('reserved_quantity', $item->quantity);
            }

            Log::info('Inventory reserved', [
                'order_id' => $this->order->id,
                'product_id' => $product->id,
                'quantity' => $item->quantity,
            ]);
        }
    }

    /**
     * Send order to external fulfillment system.
     */
    private function sendToFulfillmentSystem(): void
    {
        // This would integrate with external fulfillment systems like ShipStation, ShipBob, etc.
        $fulfillmentData = [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer' => [
                'name' => $this->order->user->name,
                'email' => $this->order->user->email,
                'phone' => $this->order->shipping_address['phone'] ?? null,
            ],
            'shipping_address' => $this->order->shipping_address,
            'items' => $this->order->items->map(function ($item) {
                return [
                    'sku' => $item->product_sku,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_price,
                ];
            })->toArray(),
            'total' => $this->order->total,
            'currency' => $this->order->currency,
        ];

        // Simulate API call to fulfillment system
        $response = \Http::post(config('services.fulfillment.endpoint'), $fulfillmentData);

        if ($response->successful()) {
            Log::info('Order sent to fulfillment system', [
                'order_id' => $this->order->id,
                'fulfillment_id' => $response->json('id'),
            ]);

            // Update order with fulfillment ID
            $this->order->update([
                'fulfillment_id' => $response->json('id'),
                'sent_to_fulfillment_at' => now(),
            ]);
        } else {
            throw new \Exception('Failed to send order to fulfillment system: ' . $response->body());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Order processing job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mark order as failed
        $this->order->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        // Notify admin of permanent failure
        try {
            $adminUsers = \App\Models\User::role('admin')->get();
            foreach ($adminUsers as $admin) {
                \Mail::to($admin->email)
                    ->send(new \App\Mail\AdminNotificationMail(
                        'Order Processing Failed',
                        "Order #{$this->order->order_number} failed to process after {$this->attempts()} attempts",
                        $exception
                    ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admin of order processing failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get number of times the job may be attempted.
     */
    public function tries(): int
    {
        return 5;
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}

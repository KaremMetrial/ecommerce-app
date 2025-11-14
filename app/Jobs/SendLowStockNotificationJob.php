<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLowStockNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product,
        public bool $isCritical = false
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get admin users who should receive notifications
            $adminUsers = \App\Models\User::role('admin')->get();

            foreach ($adminUsers as $admin) {
                Mail::to($admin->email)
                    ->send(new \App\Mail\LowStockNotificationMail($this->product, $this->isCritical));
            }

            Log::info('Low stock notification sent to admins', [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'stock_quantity' => $this->product->stock_quantity,
                'is_critical' => $this->isCritical,
                'admin_count' => $adminUsers->count(),
            ]);

            // Also send to external monitoring service if configured
            if (config('services.slack.webhook_url')) {
                $this->sendSlackNotification();
            }

        } catch (\Exception $e) {
            Log::error('Failed to send low stock notification', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry job with exponential backoff
            $this->release(300); // 5 minutes delay
        }
    }

    /**
     * Send Slack notification.
     */
    private function sendSlackNotification(): void
    {
        $webhookUrl = config('services.slack.webhook_url');

        $message = [
            'text' => $this->isCritical
                ? "🚨 *Product Out of Stock*"
                : "⚠️ *Low Stock Alert*",
            'attachments' => [
                [
                    'color' => $this->isCritical ? 'danger' : 'warning',
                    'fields' => [
                        [
                            'title' => 'Product',
                            'value' => $this->product->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'SKU',
                            'value' => $this->product->sku,
                            'short' => true,
                        ],
                        [
                            'title' => 'Current Stock',
                            'value' => $this->product->stock_quantity,
                            'short' => true,
                        ],
                        [
                            'title' => 'Status',
                            'value' => $this->isCritical ? 'Out of Stock' : 'Low Stock',
                            'short' => true,
                        ],
                    ],
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => 'View Product',
                            'url' => route('admin.products.show', $this->product->id),
                        ],
                    ],
                ],
            ],
        ];

        \Http::post($webhookUrl, $message);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Low stock notification job failed permanently', [
            'product_id' => $this->product->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Send critical failure notification to main admin
        if ($this->isCritical) {
            try {
                $mainAdmin = \App\Models\User::role('admin')->first();
                if ($mainAdmin) {
                    Mail::to($mainAdmin->email)
                        ->send(new \App\Mail\AdminNotificationMail(
                            'Critical: Low Stock Notification Failed',
                            "Failed to send low stock notification for product: {$this->product->name}",
                            $exception
                        ));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send admin failure notification', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get number of times job may be attempted.
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
        return now()->addHours(6);
    }
}

<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\SendOrderConfirmationEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderConfirmationEmail implements ShouldQueue
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
    public function handle(OrderCreated $event): void
    {
        // Dispatch job to send order confirmation email
        SendOrderConfirmationEmailJob::dispatch($event->order);
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderCreated $event, \Throwable $exception): void
    {
        \Log::error('Failed to send order confirmation email', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

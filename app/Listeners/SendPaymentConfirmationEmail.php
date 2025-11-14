<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Jobs\SendPaymentConfirmationEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentConfirmationEmail implements ShouldQueue
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
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        try {
            // Dispatch job to send payment confirmation email
            SendPaymentConfirmationEmailJob::dispatch($payment);

            Log::info('Payment confirmation email job dispatched', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch payment confirmation email job', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentCompleted $event, \Throwable $exception): void
    {
        Log::error('Failed to send payment confirmation email', [
            'payment_id' => $event->payment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

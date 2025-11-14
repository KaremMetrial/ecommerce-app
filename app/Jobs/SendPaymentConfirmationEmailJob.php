<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentConfirmationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Payment $payment
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = $this->payment->order->user;

            // Send payment confirmation email
            Mail::to($user->email)
                ->send(new \App\Mail\PaymentConfirmationMail($this->payment));

            Log::info('Payment confirmation email sent', [
                'payment_id' => $this->payment->id,
                'order_id' => $this->payment->order_id,
                'user_id' => $user->id,
                'email' => $user->email,
                'amount' => $this->payment->amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email', [
                'payment_id' => $this->payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry job with exponential backoff
            $this->release(60); // 1 minute delay
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Payment confirmation email job failed permanently', [
            'payment_id' => $this->payment->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Optionally notify admin of permanent failure
        // Mail::to(config('app.admin_email'))->send(new AdminNotificationMail($exception));
    }

    /**
     * Get the number of times the job may be attempted.
     */
    public function tries(): int
    {
        return 3;
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }
}

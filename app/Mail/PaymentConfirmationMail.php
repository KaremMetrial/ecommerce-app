<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Payment $payment
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Confirmation - Order #' . $this->payment->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payments.confirmation',
            with: [
                'payment' => $this->payment,
                'order' => $this->payment->order,
                'user' => $this->payment->order->user,
                'amount' => $this->payment->amount,
                'currency' => $this->payment->currency,
                'paymentMethod' => $this->payment->payment_method,
                'transactionId' => $this->payment->transaction_id,
                'paidAt' => $this->payment->paid_at,
            ]
        );
    }
}

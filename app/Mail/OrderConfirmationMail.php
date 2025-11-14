<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.confirmation',
            with: [
                'order' => $this->order,
                'user' => $this->order->user,
                'items' => $this->order->items,
                'shippingAddress' => $this->order->shipping_address,
                'billingAddress' => $this->order->billing_address,
                'subtotal' => $this->order->subtotal,
                'taxAmount' => $this->order->tax_amount,
                'shippingAmount' => $this->order->shipping_amount,
                'discountAmount' => $this->order->discount_amount,
                'total' => $this->order->total,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            // Optionally attach PDF invoice
            // Attachment::fromPath($this->generateInvoicePdf())
            //     ->as('invoice-' . $this->order->order_number . '.pdf')
            //     ->withMime('application/pdf'),
        ];
    }

    /**
     * Generate PDF invoice (placeholder method).
     */
    private function generateInvoicePdf(): string
    {
        // This would generate a PDF invoice using a library like DomPDF
        // For now, return empty string
        return '';
    }
}

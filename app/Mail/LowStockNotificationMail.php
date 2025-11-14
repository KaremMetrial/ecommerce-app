<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Product $product,
        public bool $isCritical = false
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isCritical
            ? '🚨 Product Out of Stock: ' . $this->product->name
            : '⚠️ Low Stock Alert: ' . $this->product->name;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.low-stock',
            with: [
                'product' => $this->product,
                'isCritical' => $this->isCritical,
                'stockQuantity' => $this->product->stock_quantity,
                'productName' => $this->product->name,
                'productSku' => $this->product->sku,
                'productUrl' => route('admin.products.show', $this->product->id),
                'alertLevel' => $this->isCritical ? 'critical' : 'warning',
            ]
        );
    }
}

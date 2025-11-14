<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $subject,
        public string $message,
        public ?\Throwable $exception = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Admin Alert] ' . $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.notification',
            with: [
                'subject' => $this->subject,
                'message' => $this->message,
                'exception' => $this->exception,
                'hasException' => $this->exception !== null,
                'errorDetails' => $this->exception ? [
                    'message' => $this->exception->getMessage(),
                    'file' => $this->exception->getFile(),
                    'line' => $this->exception->getLine(),
                    'trace' => $this->exception->getTraceAsString(),
                ] : null,
            ]
        );
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConstituencyOfficerNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $headline,
        public array $lines,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->headline . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.constituency-officer-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class ReminderMail extends Mailable
{
    public function __construct(
        private readonly string $label,
        private readonly string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'trakk · '.mb_strtolower($this->label));
    }

    public function content(): Content
    {
        return new Content(text: 'mail.reminder', with: ['body' => $this->body]);
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerificationCode extends Mailable
{
    use Queueable;

    public function __construct(public readonly string $code, public readonly int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->code.' is your '.config('app.name').' verification code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verification-code');
    }
}

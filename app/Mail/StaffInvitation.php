<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $staff, public readonly string $link) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Set up your '.config('app.name').' staff account');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.staff-invitation');
    }
}

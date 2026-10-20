<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alert to the sales address about a new website inquiry.
 */
class InquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New inquiry '.$this->inquiry->reference_code.' - '.$this->inquiry->client_name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.inquiry-received');
    }
}

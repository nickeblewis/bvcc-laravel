<?php

namespace App\Mail;

use App\Models\HireRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HireRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public HireRequest $hireRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Equipment hire request from {$this->hireRequest->name}",
            replyTo: [new Address($this->hireRequest->email, $this->hireRequest->name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.hire-request');
    }
}

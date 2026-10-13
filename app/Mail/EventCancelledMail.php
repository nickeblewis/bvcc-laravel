<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Cancelled: {$this->booking->event->title}",
            replyTo: [config('site.bookings_email')],
        );
    }

    public function content(): Content
    {
        $paid = $this->booking->paidPence();

        return new Content(markdown: 'mail.event-cancelled', with: [
            'event' => $this->booking->event,
            'paid' => $paid > 0 ? Money::format($paid) : null,
        ]);
    }
}

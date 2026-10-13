<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment due: {$this->booking->event->title}",
            replyTo: [config('site.bookings_email')],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.payment-reminder', with: [
            'event' => $this->booking->event,
            'balance' => Money::format($this->booking->balancePence()),
        ]);
    }
}

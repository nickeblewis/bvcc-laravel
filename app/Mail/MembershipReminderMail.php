<?php

namespace App\Mail;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Membership $membership) {}

    public function isRenewal(): bool
    {
        return $this->membership->status !== MembershipStatus::Pending;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isRenewal()
                ? 'Your '.config('site.short_name').' membership is due for renewal'
                : 'Payment due for your '.config('site.short_name').' membership',
            replyTo: [config('site.memberships_email')],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.membership-reminder', with: [
            'renewal' => $this->isRenewal(),
            'balance' => Money::format($this->membership->balancePence()),
        ]);
    }
}

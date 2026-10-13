<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\FeeBand;
use App\Mail\EventCancelledMail;
use App\Mail\PaymentReminderMail;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Member;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class Bookings
{
    public function __construct(private FeeCalculator $fees) {}

    /**
     * Book people onto an event.
     *
     * Each attendee is either a member (member_id) or a guest (name). The
     * price comes from the fee table for the attendee's band on the day of
     * the event, unless a waiver reason is given, in which case it is zero.
     * When the event has too few places left the whole booking goes on the
     * waiting list.
     *
     * @param  array{member_id?: int|null, contact_name?: string|null, contact_email?: string|null, contact_phone?: string|null, notes?: string|null}  $contact
     * @param  array<int, array{member_id?: int|null, name?: string|null, waived_reason?: string|null}>  $attendees
     */
    public function create(Event $event, array $contact, array $attendees): Booking
    {
        if ($event->isCancelled()) {
            throw new DomainException('This event has been cancelled.');
        }

        if ($attendees === []) {
            throw new DomainException('A booking needs at least one attendee.');
        }

        return DB::transaction(function () use ($event, $contact, $attendees): Booking {
            $booker = isset($contact['member_id']) ? Member::find($contact['member_id']) : null;
            $placesLeft = $event->placesLeft();

            $booking = $event->bookings()->create([
                'member_id' => $booker?->id,
                'contact_name' => $contact['contact_name'] ?? $booker->name ?? 'Unknown',
                'contact_email' => $contact['contact_email'] ?? $booker?->email,
                'contact_phone' => $contact['contact_phone'] ?? $booker?->phone,
                'notes' => $contact['notes'] ?? null,
                'status' => $placesLeft !== null && $placesLeft < count($attendees)
                    ? BookingStatus::Waiting
                    : BookingStatus::Pending,
            ]);

            foreach ($attendees as $attendee) {
                $member = isset($attendee['member_id']) ? Member::find($attendee['member_id']) : null;
                $band = $member?->feeBand($event->starts_at) ?? FeeBand::NonMember;
                $waived = filled($attendee['waived_reason'] ?? null);

                $booking->attendees()->create([
                    'member_id' => $member?->id,
                    'name' => $member->name ?? $attendee['name'] ?? 'Guest',
                    'fee_band' => $band,
                    'price_pence' => $waived ? 0 : (int) $this->fees->priceFor($event, $band),
                    'waived_reason' => $attendee['waived_reason'] ?? null,
                ]);
            }

            $booking->refreshStatus();

            return $booking;
        });
    }

    public function cancel(Booking $booking, ?string $reason = null): void
    {
        $booking->update([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
    }

    /**
     * Move a booking off the waiting list, if the event now has room for it.
     */
    public function promote(Booking $booking): void
    {
        if ($booking->status !== BookingStatus::Waiting) {
            throw new DomainException('Only bookings on the waiting list can be promoted.');
        }

        $placesLeft = $booking->event->placesLeft();

        if ($placesLeft !== null && $placesLeft < $booking->attendees()->count()) {
            throw new DomainException('There are not enough places left for this booking.');
        }

        $booking->update(['status' => BookingStatus::Pending]);
        $booking->refreshStatus();
    }

    /**
     * Email the booker to ask for payment. Returns false when there is no
     * address to send to.
     */
    public function chase(Booking $booking): bool
    {
        if (blank($booking->contact_email)) {
            return false;
        }

        Mail::to($booking->contact_email)->send(new PaymentReminderMail($booking));
        $booking->update(['last_chased_at' => now()]);

        return true;
    }

    /**
     * Cancel an event and tell everyone who holds a place or is waiting.
     * Bookings are kept, so payments can be refunded against them.
     *
     * @return int how many people were emailed
     */
    public function cancelEvent(Event $event, ?string $reason = null): int
    {
        $event->update([
            'status' => EventStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        $bookings = $event->bookings()
            ->where('status', '!=', BookingStatus::Cancelled)
            ->whereNotNull('contact_email')
            ->get();

        foreach ($bookings as $booking) {
            Mail::to($booking->contact_email)->send(new EventCancelledMail($booking));
        }

        return $bookings->count();
    }
}

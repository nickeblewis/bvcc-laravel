<?php

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\FeeBand;
use App\Enums\FeeType;
use App\Enums\PaymentMethod;
use App\Mail\EventCancelledMail;
use App\Mail\PaymentReminderMail;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Member;
use App\Models\MembershipTier;
use App\Services\Bookings;
use App\Services\EventSeries;
use App\Services\Memberships;
use App\Services\Payments;
use Database\Seeders\ClubSeeder;
use Illuminate\Support\Facades\Mail;

// F-34 and F-35: bookings, payments, capacity and the waiting list (docs/admin.md).

beforeEach(function () {
    $this->seed(ClubSeeder::class);
    $this->bookings = app(Bookings::class);
    $this->payments = app(Payments::class);
});

it('prices each attendee from their own membership band', function () {
    $event = Event::factory()->create(['fee_type' => FeeType::Session]);
    $member = Member::factory()->create();
    $tier = MembershipTier::where('slug', 'adult-pay-as-you-paddle')->firstOrFail();
    $this->payments->record(app(Memberships::class)->create($member, $tier), 3500, PaymentMethod::Cash);

    $booking = $this->bookings->create($event, ['member_id' => $member->id], [
        ['member_id' => $member->id],
        ['name' => 'A friend'],
    ]);

    expect($booking->attendees->pluck('price_pence', 'name')->all())->toBe([$member->name => 600, 'A friend' => 1000])
        ->and($booking->attendees[1]->fee_band)->toBe(FeeBand::NonMember)
        ->and($booking->totalPence())->toBe(1600)
        ->and($booking->status)->toBe(BookingStatus::Pending);
});

it('takes the booker\'s contact details from their member record', function () {
    $member = Member::factory()->create(['email' => 'sam@example.com']);

    $booking = $this->bookings->create(Event::factory()->create(), ['member_id' => $member->id], [['member_id' => $member->id]]);

    expect($booking->contact_name)->toBe($member->name)->and($booking->contact_email)->toBe('sam@example.com');
});

it('confirms a booking once it is paid in full', function () {
    $booking = $this->bookings->create(Event::factory()->create(), ['contact_name' => 'Jo'], [['name' => 'Jo']]);

    $this->payments->record($booking, 400, PaymentMethod::Cash);
    expect($booking->fresh()->status)->toBe(BookingStatus::Pending)->and($booking->balancePence())->toBe(600);

    $this->payments->record($booking, 600, PaymentMethod::BankTransfer, reference: 'JO SESSION');
    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('confirms a booking with nothing to pay straight away', function () {
    $event = Event::factory()->create(['fee_type' => FeeType::Free]);

    expect($this->bookings->create($event, ['contact_name' => 'Jo'], [['name' => 'Jo']])->status)->toBe(BookingStatus::Confirmed);
});

it('charges nothing when the fee is waived, and records why', function () {
    $booking = $this->bookings->create(Event::factory()->create(), ['contact_name' => 'Jo'], [['name' => 'Jo', 'waived_reason' => 'Volunteer']]);

    expect($booking->totalPence())->toBe(0)
        ->and($booking->attendees[0]->waived_reason)->toBe('Volunteer')
        ->and($booking->status)->toBe(BookingStatus::Confirmed);
});

it('goes back to awaiting payment after a refund', function () {
    $booking = $this->bookings->create(Event::factory()->create(), ['contact_name' => 'Jo'], [['name' => 'Jo']]);
    $this->payments->record($booking, 1000, PaymentMethod::Cash);

    $this->payments->record($booking, -1000, PaymentMethod::Cash);

    expect($booking->fresh()->status)->toBe(BookingStatus::Pending)->and($booking->paidPence())->toBe(0);
});

it('puts a booking on the waiting list when the event is full', function () {
    $event = Event::factory()->create(['capacity' => 2]);

    $first = $this->bookings->create($event, ['contact_name' => 'A'], [['name' => 'A'], ['name' => 'B']]);
    $second = $this->bookings->create($event, ['contact_name' => 'C'], [['name' => 'C']]);

    expect($first->status)->toBe(BookingStatus::Pending)
        ->and($second->status)->toBe(BookingStatus::Waiting)
        ->and($event->placesLeft())->toBe(0);
});

it('waits as a whole when there is not room for everyone on the booking', function () {
    $event = Event::factory()->create(['capacity' => 2]);
    $this->bookings->create($event, ['contact_name' => 'A'], [['name' => 'A']]);

    expect($this->bookings->create($event, ['contact_name' => 'B'], [['name' => 'B'], ['name' => 'C']])->status)->toBe(BookingStatus::Waiting);
});

it('frees the places when a booking is cancelled and lets a waiting booking be promoted', function () {
    $event = Event::factory()->create(['capacity' => 1]);
    $first = $this->bookings->create($event, ['contact_name' => 'A'], [['name' => 'A']]);
    $waiting = $this->bookings->create($event, ['contact_name' => 'B'], [['name' => 'B']]);

    expect(fn () => $this->bookings->promote($waiting))->toThrow(DomainException::class);

    $this->bookings->cancel($first, 'Ill');
    $this->bookings->promote($waiting);

    expect($first->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($first->fresh()->cancel_reason)->toBe('Ill')
        ->and($waiting->fresh()->status)->toBe(BookingStatus::Pending)
        ->and($event->placesLeft())->toBe(0);
});

it('refuses bookings on a cancelled event', function () {
    $event = Event::factory()->create(['status' => EventStatus::Cancelled]);

    expect(fn () => $this->bookings->create($event, ['contact_name' => 'A'], [['name' => 'A']]))->toThrow(DomainException::class);
});

it('lists unpaid bookings to chase, but not for cancelled events', function () {
    $unpaid = $this->bookings->create(Event::factory()->create(), ['contact_name' => 'A'], [['name' => 'A']]);
    $paid = $this->bookings->create(Event::factory()->create(), ['contact_name' => 'B'], [['name' => 'B']]);
    $this->payments->record($paid, 1000, PaymentMethod::Cash);
    $cancelledEvent = Event::factory()->create();
    $this->bookings->create($cancelledEvent, ['contact_name' => 'C'], [['name' => 'C']]);
    $this->bookings->cancelEvent($cancelledEvent);

    expect(Booking::toChase()->pluck('id')->all())->toBe([$unpaid->id]);
});

it('emails a payment reminder and records when', function () {
    Mail::fake();
    $booking = $this->bookings->create(Event::factory()->create(), ['contact_name' => 'Jo', 'contact_email' => 'jo@example.com'], [['name' => 'Jo']]);

    expect($this->bookings->chase($booking))->toBeTrue();

    Mail::assertSent(PaymentReminderMail::class, fn ($mail) => $mail->hasTo('jo@example.com'));
    expect($booking->fresh()->last_chased_at)->not->toBeNull();
});

it('tells everyone booked or waiting when an event is cancelled, and keeps their bookings', function () {
    Mail::fake();
    $event = Event::factory()->create(['capacity' => 1]);
    $this->bookings->create($event, ['contact_name' => 'A', 'contact_email' => 'a@example.com'], [['name' => 'A']]);
    $this->bookings->create($event, ['contact_name' => 'B', 'contact_email' => 'b@example.com'], [['name' => 'B']]);
    $gone = $this->bookings->create($event, ['contact_name' => 'C', 'contact_email' => 'c@example.com'], [['name' => 'C']]);
    $this->bookings->cancel($gone);

    expect($this->bookings->cancelEvent($event, 'River too low'))->toBe(2);

    Mail::assertSent(EventCancelledMail::class, 2);
    Mail::assertNotSent(EventCancelledMail::class, fn ($mail) => $mail->hasTo('c@example.com'));
    expect($event->fresh()->status)->toBe(EventStatus::Cancelled)->and($event->bookings()->count())->toBe(3);
});

it('renders the reminder with the amount owed and no bank details from code', function () {
    $booking = $this->bookings->create(Event::factory()->create(['title' => 'Lido session']), ['contact_name' => 'Jo', 'contact_email' => 'jo@example.com'], [['name' => 'Jo']]);

    $html = (new PaymentReminderMail($booking))->render();

    expect($html)->toContain('£10')->toContain('Lido session')->toContain(e(config('site.payment_instructions')));
});

// F-33: weekly series.

it('creates one event a week between two dates on the chosen weekday', function () {
    $events = app(EventSeries::class)->generate(
        Event::factory()->raw(['title' => 'Lido session', 'starts_at' => null, 'ends_at' => null]),
        weekday: 3,
        from: '2027-05-24',
        to: '2027-06-20',
        startTime: '19:00',
        endTime: '20:30',
    );

    expect($events)->toHaveCount(4)
        ->and($events->map(fn (Event $e) => $e->starts_at->format('D Y-m-d H:i'))->all())
        ->toBe(['Wed 2027-05-26 19:00', 'Wed 2027-06-02 19:00', 'Wed 2027-06-09 19:00', 'Wed 2027-06-16 19:00'])
        ->and($events->pluck('series_id')->unique())->toHaveCount(1)
        ->and($events->pluck('slug')->unique())->toHaveCount(4)
        ->and($events[0]->ends_at->format('H:i'))->toBe('20:30');
});

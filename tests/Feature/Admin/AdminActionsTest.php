<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Filament\Resources\Bookings\Pages\ManageBookings;
use App\Filament\Resources\Events\Pages\EventRegister;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Members\Pages\EditMember;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Widgets\BookingsToChase;
use App\Filament\Widgets\ClubStats;
use App\Filament\Widgets\MembershipsToChase;
use App\Filament\Widgets\UpcomingEvents;
use App\Models\Event;
use App\Models\Member;
use App\Models\MembershipTier;
use App\Services\Bookings;
use App\Services\Memberships;
use Database\Seeders\ClubSeeder;
use Livewire\Livewire;

// F-31 to F-37: what staff do in the admin (docs/admin.md).

beforeEach(fn () => $this->seed(ClubSeeder::class));

it('records a payment against a booking from the bookings list', function () {
    $admin = staff();
    $booking = app(Bookings::class)->create(Event::factory()->create(), ['contact_name' => 'Jo'], [['name' => 'Jo']]);

    Livewire::actingAs($admin)->test(ManageBookings::class)
        ->callTableAction('recordPayment', $booking, data: [
            'amount_pence' => '10.00',
            'method' => PaymentMethod::Cash->value,
            'received_on' => today()->toDateString(),
            'reference' => 'On the day',
        ])
        ->assertHasNoTableActionErrors();

    $payment = $booking->payments()->sole();

    expect($payment->amount_pence)->toBe(1000)
        ->and($payment->method)->toBe(PaymentMethod::Cash)
        ->and($payment->recorded_by)->toBe($admin->id)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('offers the amount still owed when recording a payment', function () {
    $booking = app(Bookings::class)->create(Event::factory()->create(), ['contact_name' => 'Jo'], [['name' => 'Jo']]);

    Livewire::actingAs(staff())->test(ManageBookings::class)
        ->mountTableAction('recordPayment', $booking)
        ->assertTableActionDataSet(['amount_pence' => '10.00']);
});

it('hides the payment action from roles that do not handle money', function () {
    $booking = app(Bookings::class)->create(Event::factory()->create(), ['contact_name' => 'Jo'], [['name' => 'Jo']]);

    Livewire::actingAs(staff(UserRole::Welfare))->test(ManageBookings::class)
        ->assertTableActionHidden('recordPayment', $booking);
});

it('adds a booking for a guest from the bookings list', function () {
    $event = Event::factory()->create();

    Livewire::actingAs(staff(UserRole::Events))->test(ManageBookings::class)
        ->callAction('createBooking', data: [
            'event_id' => $event->id,
            'contact_name' => 'Guest Paddler',
            'contact_email' => 'guest@example.com',
        ])
        ->assertHasNoActionErrors();

    $booking = $event->bookings()->sole();

    expect($booking->contact_name)->toBe('Guest Paddler')
        ->and($booking->attendees()->sole()->price_pence)->toBe(1000);
});

it('plans a season of weekly sessions from the events list', function () {
    Livewire::actingAs(staff(UserRole::Events))->test(ListEvents::class)
        ->callAction('createSeries', data: [
            'title' => 'Lido session',
            'weekday' => 3,
            'start_time' => '19:00',
            'end_time' => '20:30',
            'from' => '2027-06-01',
            'to' => '2027-06-30',
            'capacity' => 30,
        ])
        ->assertHasNoActionErrors();

    expect(Event::where('title', 'Lido session')->count())->toBe(5)
        ->and(Event::where('title', 'Lido session')->first()->capacity)->toBe(30);
});

it('shows medical details on a member only to welfare staff and administrators', function (UserRole $role, bool $visible) {
    $member = Member::factory()->create(['medical_notes' => 'Asthma']);

    $page = Livewire::actingAs(staff($role))->test(EditMember::class, ['record' => $member->id]);

    $visible
        ? $page->assertFormFieldVisible('medical_notes')
        : $page->assertFormFieldHidden('medical_notes');
})->with([
    [UserRole::Admin, true],
    [UserRole::Welfare, true],
    [UserRole::Membership, false],
    [UserRole::Treasurer, false],
    [UserRole::Events, false],
]);

it('keeps medical details when someone who cannot see them saves the member', function () {
    $member = Member::factory()->create(['medical_notes' => 'Asthma']);

    Livewire::actingAs(staff(UserRole::Membership))->test(EditMember::class, ['record' => $member->id])
        ->fillForm(['phone' => '07000 000000'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($member->fresh()->medical_notes)->toBe('Asthma')
        ->and($member->fresh()->getAttribute('phone'))->toBe('07000 000000');
});

it('shows medical details on a register to the event\'s leader but not to other leaders', function () {
    $leader = staff(UserRole::Leader);
    $event = Event::factory()->create(['leader_id' => $leader->id]);
    $member = Member::factory()->create(['medical_notes' => 'Carries an EpiPen']);
    app(Bookings::class)->create($event, ['member_id' => $member->id], [['member_id' => $member->id]]);

    Livewire::actingAs($leader)->test(EventRegister::class, ['record' => $event->id])
        ->assertSee($member->name)
        ->assertSee('Carries an EpiPen');

    Livewire::actingAs(staff(UserRole::Leader))->test(EventRegister::class, ['record' => $event->id])
        ->assertSee($member->name)
        ->assertDontSee('Carries an EpiPen');
});

it('lets the register tick someone as attended', function () {
    $event = Event::factory()->create();
    $attendee = app(Bookings::class)->create($event, ['contact_name' => 'Jo'], [['name' => 'Jo']])->attendees()->sole();

    Livewire::actingAs(staff())->test(EventRegister::class, ['record' => $event->id])
        ->call('updateTableColumnState', 'attended_at', (string) $attendee->id, true);

    expect($attendee->fresh()->attended_at)->not->toBeNull();
});

// F-38: the dashboard.

it('lists unpaid bookings for upcoming events on the dashboard, soonest first', function () {
    $later = app(Bookings::class)->create(Event::factory()->create(['starts_at' => now()->addDays(20)]), ['contact_name' => 'Later Lou'], [['name' => 'Lou']]);
    $sooner = app(Bookings::class)->create(Event::factory()->create(['starts_at' => now()->addDays(3)]), ['contact_name' => 'Sooner Sam'], [['name' => 'Sam']]);
    $past = app(Bookings::class)->create(Event::factory()->create(['starts_at' => now()->subDays(3)]), ['contact_name' => 'Past Pat'], [['name' => 'Pat']]);

    Livewire::actingAs(staff())->test(BookingsToChase::class)
        ->assertCanSeeTableRecords([$sooner, $later], inOrder: true)
        ->assertCanNotSeeTableRecords([$past]);
});

it('lists unpaid and soon-to-expire memberships on the dashboard', function () {
    $memberships = app(Memberships::class);
    $paidTier = MembershipTier::where('slug', 'adult-pay-as-you-paddle')->firstOrFail();
    $freeTier = MembershipTier::where('slug', 'non-member')->firstOrFail();

    $unpaid = $memberships->create(Member::factory()->create(), $paidTier);
    $expiring = $memberships->create(Member::factory()->create(), $freeTier, today()->subYear()->addDays(5));
    $fine = $memberships->create(Member::factory()->create(), $freeTier);

    Livewire::actingAs(staff())->test(MembershipsToChase::class)
        ->assertCanSeeTableRecords([$unpaid, $expiring])
        ->assertCanNotSeeTableRecords([$fine]);
});

it('shows the headline numbers and upcoming events on the dashboard', function () {
    app(Bookings::class)->create(Event::factory()->create(['title' => 'Canal paddle', 'capacity' => 12, 'starts_at' => now()->addDay()]), ['contact_name' => 'Jo'], [['name' => 'Jo'], ['name' => 'Flo']]);

    Livewire::actingAs(staff())->test(ClubStats::class)
        ->assertSee('Money outstanding')
        ->assertSee('£20')
        ->assertSee('2 / 12 booked');

    Livewire::actingAs(staff())->test(UpcomingEvents::class)->assertSee('Canal paddle');
});

it('includes emergency and medical columns in the members CSV for welfare staff only', function (UserRole $role, bool $included) {
    Member::factory()->create(['first_name' => 'Robin', 'medical_notes' => 'Carries an EpiPen']);

    $csv = Livewire::actingAs(staff($role))->test(ListMembers::class)
        ->callAction('export')
        ->assertFileDownloaded('members-'.today()->toDateString().'.csv')
        ->effects['download']['content'];

    $csv = base64_decode($csv);

    expect($csv)->toContain('Robin');

    $included
        ? expect($csv)->toContain('Medical notes')->toContain('Carries an EpiPen')
        : expect($csv)->not->toContain('Medical notes')->not->toContain('EpiPen');
})->with([
    [UserRole::Welfare, true],
    [UserRole::Membership, false],
]);

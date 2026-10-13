<?php

use App\Enums\MembershipStatus;
use App\Enums\PaymentMethod;
use App\Mail\MembershipReminderMail;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipTier;
use App\Services\Memberships;
use App\Services\Payments;
use Database\Seeders\ClubSeeder;
use Illuminate\Support\Facades\Mail;

// F-32: memberships (docs/admin.md).

beforeEach(function () {
    $this->seed(ClubSeeder::class);
    $this->memberships = app(Memberships::class);
    $this->adult = MembershipTier::where('slug', 'adult-pay-as-you-paddle')->firstOrFail();
});

it('starts a paid tier as awaiting payment, for one year', function () {
    $membership = $this->memberships->create(Member::factory()->create(), $this->adult, today());

    expect($membership->status)->toBe(MembershipStatus::Pending)
        ->and($membership->price_pence)->toBe(3500)
        ->and($membership->expires_on->toDateString())->toBe(today()->addYear()->subDay()->toDateString());
});

it('starts a free tier as active straight away', function () {
    $free = MembershipTier::where('slug', 'non-member')->firstOrFail();

    expect($this->memberships->create(Member::factory()->create(), $free)->status)->toBe(MembershipStatus::Active);
});

it('becomes active once the balance is paid, not before', function () {
    $membership = $this->memberships->create(Member::factory()->create(), $this->adult);
    $payments = app(Payments::class);

    $payments->record($membership, 2000, PaymentMethod::Cash);
    expect($membership->fresh()->status)->toBe(MembershipStatus::Pending)
        ->and($membership->balancePence())->toBe(1500);

    $payments->record($membership, 1500, PaymentMethod::Cash);
    expect($membership->fresh()->status)->toBe(MembershipStatus::Active);
});

it('covers every person on a family membership', function () {
    $family = MembershipTier::where('slug', 'family-pay-as-you-paddle')->firstOrFail();
    [$parent, $child] = Member::factory()->count(2)->create();

    $membership = $this->memberships->create($parent, $family, coveredMemberIds: [$child->id]);
    app(Payments::class)->record($membership, 5000, PaymentMethod::BankTransfer);

    expect($membership->members)->toHaveCount(2)
        ->and($child->currentMembership()?->id)->toBe($membership->id);
});

it('renews for the following year on the same tier for the same people', function () {
    $member = Member::factory()->create();
    $old = $this->memberships->create($member, $this->adult, today()->subMonths(11));

    $new = $this->memberships->renew($old);

    expect($new->starts_on->toDateString())->toBe($old->expires_on->addDay()->toDateString())
        ->and($new->membership_tier_id)->toBe($old->membership_tier_id)
        ->and($new->members->pluck('id')->all())->toBe([$member->id]);
});

it('starts a renewal today when the old membership has lapsed', function () {
    $old = $this->memberships->create(Member::factory()->create(), $this->adult, today()->subMonths(14));

    expect($this->memberships->renew($old)->starts_on->toDateString())->toBe(today()->toDateString());
});

it('marks memberships as expired once their year has ended', function () {
    $free = MembershipTier::where('slug', 'non-member')->firstOrFail();
    $lapsed = $this->memberships->create(Member::factory()->create(), $free, today()->subMonths(13));
    $current = $this->memberships->create(Member::factory()->create(), $free);

    expect($this->memberships->expireLapsed())->toBe(1)
        ->and($lapsed->fresh()->status)->toBe(MembershipStatus::Expired)
        ->and($current->fresh()->status)->toBe(MembershipStatus::Active);
});

it('finds memberships expiring in the next 30 days', function () {
    $free = MembershipTier::where('slug', 'non-member')->firstOrFail();
    $soon = $this->memberships->create(Member::factory()->create(), $free, today()->subYear()->addDays(10));
    $this->memberships->create(Member::factory()->create(), $free);

    expect(Membership::expiringWithin(30)->pluck('id')->all())->toBe([$soon->id]);
});

it('emails the lead member a reminder and records when', function () {
    Mail::fake();
    $membership = $this->memberships->create(Member::factory()->create(['email' => 'pat@example.com']), $this->adult);

    expect($this->memberships->chase($membership))->toBeTrue();

    Mail::assertSent(MembershipReminderMail::class, fn ($mail) => $mail->hasTo('pat@example.com'));
    expect($membership->fresh()->last_chased_at)->not->toBeNull();
});

it('does not try to email a member with no address', function () {
    Mail::fake();
    $membership = $this->memberships->create(Member::factory()->create(['email' => null]), $this->adult);

    expect($this->memberships->chase($membership))->toBeFalse();
    Mail::assertNothingSent();
});

it('stores emergency and medical details encrypted and keeps them out of JSON', function () {
    $member = Member::factory()->create(['medical_notes' => 'Asthma, carries inhaler']);

    $raw = DB::table('members')->where('id', $member->id)->first();

    expect($raw->medical_notes)->not->toContain('Asthma')
        ->and($raw->emergency_contacts)->not->toContain($member->emergency_contacts[0]['name'])
        ->and($member->fresh()->medical_notes)->toBe('Asthma, carries inhaler')
        ->and($member->toArray())->not->toHaveKeys(['medical_notes', 'emergency_contacts']);
});

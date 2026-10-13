<?php

use App\Enums\FeeBand;
use App\Enums\FeeType;
use App\Enums\PaymentMethod;
use App\Models\Event;
use App\Models\Member;
use App\Models\MembershipTier;
use App\Services\FeeCalculator;
use App\Services\Memberships;
use App\Services\Payments;
use App\Support\Money;
use Database\Seeders\ClubSeeder;

// F-36: fees by membership band (docs/admin.md).

beforeEach(fn () => $this->seed(ClubSeeder::class));

it('charges the published club fee for each band and event type', function (FeeBand $band, FeeType $type, ?int $pence) {
    $event = Event::factory()->create(['fee_type' => $type]);

    expect(app(FeeCalculator::class)->priceFor($event, $band))->toBe($pence);
})->with([
    [FeeBand::NonMember, FeeType::Session, 1000],
    [FeeBand::PayAsYouPaddle, FeeType::Session, 600],
    [FeeBand::Subscriber, FeeType::Session, 0],
    [FeeBand::Affiliate, FeeType::FullDay, 1000],
    [FeeBand::NonMember, FeeType::FullDay, 1500],
    [FeeBand::NonMember, FeeType::Competition, 500],
    [FeeBand::PayAsYouPaddle, FeeType::Competition, null],
    [FeeBand::NonMember, FeeType::Free, 0],
]);

it('uses the event\'s own prices when the fee type is custom', function () {
    $event = Event::factory()->create([
        'fee_type' => FeeType::Custom,
        'custom_prices' => ['non_member' => 3000, 'pay_as_you_paddle' => 2000],
    ]);

    $fees = app(FeeCalculator::class);

    expect($fees->priceFor($event, FeeBand::NonMember))->toBe(3000)
        ->and($fees->priceFor($event, FeeBand::PayAsYouPaddle))->toBe(2000)
        ->and($fees->priceFor($event, FeeBand::Subscriber))->toBeNull();
});

it('treats someone with no current membership as a non-member', function () {
    expect(Member::factory()->create()->feeBand())->toBe(FeeBand::NonMember);
});

it('takes a member\'s band from their active membership', function () {
    $member = Member::factory()->create();
    $tier = MembershipTier::where('slug', 'adult-pay-as-you-paddle')->firstOrFail();

    $membership = app(Memberships::class)->create($member, $tier);

    // Not paid yet, so not a member yet.
    expect($member->feeBand())->toBe(FeeBand::NonMember);

    app(Payments::class)->record($membership, 3500, PaymentMethod::BankTransfer);

    expect($member->feeBand())->toBe(FeeBand::PayAsYouPaddle);
});

it('formats pence as pounds', function () {
    expect(Money::format(1000))->toBe('£10')
        ->and(Money::format(750))->toBe('£7.50')
        ->and(Money::format(-600))->toBe('-£6')
        ->and(Money::format(null))->toBe('n/a');
});

<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\FeeType;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Member;
use App\Models\MembershipTier;
use App\Models\User;
use App\Models\Venue;
use App\Services\Bookings;
use App\Services\EventSeries;
use App\Services\Memberships;
use App\Services\Payments;
use Illuminate\Database\Seeder;

/**
 * Made-up people, bookings and payments for local development, so the admin
 * has something to show. Never run this in production.
 */
class DemoSeeder extends Seeder
{
    public function run(Memberships $memberships, Bookings $bookings, Payments $payments, EventSeries $series): void
    {
        $staff = [
            ['Admin', 'admin@bvcc.test', UserRole::Admin],
            ['Membership Secretary', 'membership@bvcc.test', UserRole::Membership],
            ['Programme Coordinator', 'events@bvcc.test', UserRole::Events],
            ['Treasurer', 'treasurer@bvcc.test', UserRole::Treasurer],
            ['Welfare Officer', 'welfare@bvcc.test', UserRole::Welfare],
            ['Trip Leader', 'leader@bvcc.test', UserRole::Leader],
            ['Website Editor', 'editor@bvcc.test', UserRole::Editor],
        ];

        foreach ($staff as [$name, $email, $role]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
            ]);

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
        }

        if (Member::exists()) {
            return;
        }

        $admin = User::where('email', 'admin@bvcc.test')->firstOrFail();
        $leader = User::where('email', 'leader@bvcc.test')->firstOrFail();

        // A winter of pool sessions, so the calendar and "next session" have content.
        $series->generate([
            'title' => 'Winter pool session',
            'summary' => 'Our weekly session in the warm indoor pool. All levels welcome; great for learning to roll.',
            'description' => 'Open to all levels, age 8 and up. Club boats and kit are provided. Space on the water is limited, so please book.',
            'event_category_id' => EventCategory::where('slug', EventCategory::SESSIONS)->firstOrFail()->id,
            'venue_id' => Venue::where('slug', 'yateley-manor-school-pool')->firstOrFail()->id,
            'capacity' => 10,
            'suitability' => 'All levels, age 8 and up',
            'fee_type' => FeeType::Session,
            'status' => EventStatus::Published,
        ], weekday: 3, from: today()->toDateString(), to: today()->addMonths(5)->toDateString(), startTime: '20:00', endTime: '21:45');

        Event::whereNull('leader_id')->where('slug', 'like', 'barle-%')->update(['leader_id' => $leader->id, 'capacity' => 8]);

        $tiers = MembershipTier::all()->keyBy('slug');
        $members = Member::factory()->count(36)->create();

        // A spread of memberships: paid, unpaid, expiring soon and lapsed.
        foreach ($members->take(28) as $i => $member) {
            $tier = match (true) {
                $i < 10 => $tiers['adult-pay-as-you-paddle'],
                $i < 15 => $tiers['adult-monthly-subscriber'],
                $i < 20 => $tiers['youth-or-student-pay-as-you-paddle'],
                $i < 23 => $tiers['family-pay-as-you-paddle'],
                default => $tiers['non-member'],
            };

            $start = match (true) {
                $i % 7 === 0 => today()->subYear()->addDays(12),  // expiring soon
                $i % 9 === 0 => today()->subMonths(14),           // lapsed
                default => today()->subMonths($i % 10),
            };

            $membership = $memberships->create($member, $tier, $start);

            if ($membership->price_pence > 0 && $i % 5 !== 0) {
                $payments->record($membership, $membership->price_pence, PaymentMethod::BankTransfer, $start, 'MEMBERSHIP', $admin);
            }
        }

        $memberships->expireLapsed();

        // A member's own login (F-50), on someone with a membership.
        $member = $members->get(1);
        $member->update(['email' => 'member@bvcc.test']);
        User::create([
            'name' => $member->name,
            'email' => 'member@bvcc.test',
            'password' => 'password',
            'role' => UserRole::Member,
            'member_id' => $member->id,
        ])->markEmailAsVerified();

        // Bookings on the upcoming trips and the next two sessions.
        $events = Event::upcoming()->where('status', EventStatus::Published)->limit(6)->get();

        foreach ($events as $e => $event) {
            foreach ($members->shuffle()->take($event->capacity ? min(7, $event->capacity + 2) : 6) as $n => $member) {
                $booking = $bookings->create($event, ['member_id' => $member->id], [['member_id' => $member->id]]);

                if ($booking->balancePence() > 0 && ($n + $e) % 3 !== 0) {
                    $payments->record($booking, $booking->balancePence(), $n % 2 ? PaymentMethod::Cash : PaymentMethod::BankTransfer, today()->subDays($n), null, $admin);
                }
            }
        }
    }
}

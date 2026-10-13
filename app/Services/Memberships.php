<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Mail\MembershipReminderMail;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipTier;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class Memberships
{
    /**
     * Start a one-year membership. It is active straight away when the tier
     * is free, and waits for payment otherwise.
     *
     * @param  array<int, int>  $coveredMemberIds  other people covered (family tiers)
     */
    public function create(Member $lead, MembershipTier $tier, ?CarbonInterface $startsOn = null, array $coveredMemberIds = []): Membership
    {
        $startsOn ??= today();

        return DB::transaction(function () use ($lead, $tier, $startsOn, $coveredMemberIds): Membership {
            $membership = Membership::create([
                'membership_tier_id' => $tier->id,
                'member_id' => $lead->id,
                'starts_on' => $startsOn,
                'expires_on' => $startsOn->copy()->addYear()->subDay(),
                'price_pence' => $tier->initial_pence,
                'monthly_pence' => $tier->monthly_pence,
                'status' => $tier->initial_pence === 0 ? MembershipStatus::Active : MembershipStatus::Pending,
            ]);

            $membership->members()->sync(array_unique([$lead->id, ...$coveredMemberIds]));

            return $membership;
        });
    }

    /**
     * Create the next year's membership for the same people on the same tier.
     * It follows on from the old one, or starts today if that has lapsed.
     */
    public function renew(Membership $membership): Membership
    {
        $startsOn = $membership->expires_on->isFuture()
            ? $membership->expires_on->copy()->addDay()
            : today();

        return $this->create(
            $membership->lead,
            $membership->tier,
            $startsOn,
            array_map(intval(...), $membership->members()->pluck('members.id')->all()),
        );
    }

    public function cancel(Membership $membership): void
    {
        $membership->update([
            'status' => MembershipStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Mark active memberships whose year has ended as expired.
     *
     * @return int how many were expired
     */
    public function expireLapsed(): int
    {
        return Membership::where('status', MembershipStatus::Active)
            ->whereDate('expires_on', '<', today())
            ->update(['status' => MembershipStatus::Expired]);
    }

    /**
     * Email the lead member about a payment due or a renewal coming up.
     * Returns false when there is no address to send to.
     */
    public function chase(Membership $membership): bool
    {
        if (blank($membership->lead->email)) {
            return false;
        }

        Mail::to($membership->lead->email)->send(new MembershipReminderMail($membership));
        $membership->update(['last_chased_at' => now()]);

        return true;
    }
}

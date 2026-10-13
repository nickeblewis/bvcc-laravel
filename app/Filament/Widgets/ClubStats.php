<?php

namespace App\Filament\Widgets;

use App\Enums\Area;
use App\Enums\MembershipStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Memberships\MembershipResource;
use App\Filament\Support\Staff;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Member;
use App\Models\Membership;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * The numbers a volunteer wants first: how many members, who is about to
 * lapse, what is owed and how the next event is filling.
 */
class ClubStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        $stats = [];

        if (Staff::can(Area::Members)) {
            $covered = Member::whereHas('memberships', fn (Builder $m) => $m->current());
            $byBand = Membership::current()->with('tier')->withCount('members')->get()
                ->groupBy(fn (Membership $m): string => $m->tier->fee_band->getLabel())
                ->map(fn ($group, string $band): string => $group->sum('members_count').' '.strtolower($band))
                ->implode(', ');

            $stats[] = Stat::make('Current members', $covered->count())
                ->description($byBand ?: 'No current memberships')
                ->url(MemberResource::getUrl());

            $expiring = Membership::expiringWithin(30)->count();

            $stats[] = Stat::make('Expiring in 30 days', $expiring)
                ->description($expiring ? 'Send renewal reminders' : 'Nothing due')
                ->color($expiring ? 'warning' : 'success')
                ->url(Staff::can(Area::Memberships) ? MembershipResource::getUrl(parameters: ['filters' => ['expiring' => ['isActive' => true]]]) : null);
        }

        if (Staff::can(Area::Payments) || Staff::can(Area::Bookings)) {
            $bookings = Booking::toChase()->with(['attendees', 'payments'])->get();
            $memberships = Membership::where('status', MembershipStatus::Pending)->with('payments')->get();
            $owed = $bookings->sum(fn (Booking $b): int => max(0, $b->balancePence()))
                + $memberships->sum(fn (Membership $m): int => max(0, $m->balancePence()));

            $stats[] = Stat::make('Money outstanding', Money::format((int) $owed))
                ->description("{$bookings->count()} bookings, {$memberships->count()} memberships")
                ->color($owed > 0 ? 'danger' : 'success')
                ->url(Staff::can(Area::Bookings) ? BookingResource::getUrl(parameters: ['filters' => ['to_chase' => ['isActive' => true]]]) : null);
        }

        if (Staff::can(Area::Events) || Staff::can(Area::Bookings)) {
            $next = Event::upcoming()->whereNull('cancelled_at')->withCount('attendees')->first();

            $stats[] = $next === null
                ? Stat::make('Next event', 'Nothing planned')
                : Stat::make('Next event', $next->capacity ? "{$next->attendees_count} / {$next->capacity} booked" : "{$next->attendees_count} booked")
                    ->description($next->title.', '.$next->starts_at->format('D j M'))
                    ->url(EventResource::getUrl('register', ['record' => $next]));
        }

        return $stats;
    }
}

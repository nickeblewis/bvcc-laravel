<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\MyMembership;
use App\Filament\Support\Staff;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * A member's dashboard (F-50): their membership at a glance, linking to
 * the My membership page.
 */
class MyMembershipSummary extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return MyMembership::canAccess();
    }

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        $membership = Staff::user()?->member?->memberships()->with('tier', 'payments')->orderByDesc('starts_on')->first();
        $url = MyMembership::getUrl();

        if ($membership === null) {
            return [Stat::make('Membership', 'None yet')->url($url)];
        }

        $stats = [
            Stat::make('Membership', $membership->status->getLabel())
                ->description($membership->tier->name)
                ->color($membership->status->getColor())
                ->url($url),
            Stat::make('Runs until', $membership->expires_on->format('j M Y'))->url($url),
        ];

        if ($membership->balancePence() > 0) {
            $stats[] = Stat::make('Still to pay', Money::format($membership->balancePence()))->color('warning')->url($url);
        }

        return $stats;
    }
}

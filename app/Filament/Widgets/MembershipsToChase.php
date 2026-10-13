<?php

namespace App\Filament\Widgets;

use App\Enums\Area;
use App\Enums\MembershipStatus;
use App\Filament\Support\MembershipTable;
use App\Filament\Support\PaymentActions;
use App\Filament\Support\Staff;
use App\Models\Membership;
use Filament\Actions\ActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class MembershipsToChase extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Staff::can(Area::Memberships);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Memberships to chase')
            ->description('Waiting for payment, running out in the next 30 days, or lapsed in the last 60.')
            ->query(Membership::query()
                ->with(['lead', 'tier', 'payments'])
                ->withCount('members')
                ->where(fn (Builder $q) => $q
                    ->where('status', MembershipStatus::Pending)
                    ->orWhere(fn (Builder $expiring) => Membership::whereExpiringWithin($expiring, 30))
                    ->orWhere(fn (Builder $lapsed) => $lapsed
                        ->where('status', MembershipStatus::Expired)
                        ->whereDate('expires_on', '>=', today()->subDays(60))
                        // Not if they have already renewed.
                        ->whereDoesntHave('lead.memberships', fn (Builder $m) => $m->whereIn('status', [MembershipStatus::Active, MembershipStatus::Pending]))))
                ->orderBy('expires_on'))
            ->columns(MembershipTable::columns())
            ->recordActions([
                PaymentActions::record(),
                MembershipTable::chase(),
                ActionGroup::make([MembershipTable::renew(), PaymentActions::refund(), MembershipTable::cancel()]),
            ])
            ->paginated([5, 10, 25])
            ->emptyStateHeading('All memberships are up to date');
    }
}

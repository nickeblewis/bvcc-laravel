<?php

namespace App\Filament\Widgets;

use App\Enums\Area;
use App\Filament\Support\BookingActions;
use App\Filament\Support\BookingTable;
use App\Filament\Support\PaymentActions;
use App\Filament\Support\Staff;
use App\Models\Booking;
use Filament\Actions\ActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class BookingsToChase extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Staff::can(Area::Bookings);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Bookings to chase')
            ->description('Places held but not paid for, soonest event first.')
            ->query(BookingTable::eagerLoad(Booking::query()->toChase())
                ->join('events', 'events.id', '=', 'bookings.event_id')
                ->where('events.starts_at', '>=', today())
                ->orderBy('events.starts_at')
                ->select('bookings.*'))
            ->columns(BookingTable::columns())
            ->recordActions([
                PaymentActions::record(),
                BookingActions::chase(),
                ActionGroup::make([PaymentActions::refund(), BookingActions::cancel()]),
            ])
            ->paginated([5, 10, 25])
            ->emptyStateHeading('Everyone has paid')
            ->emptyStateDescription('No upcoming bookings are waiting for payment.');
    }
}

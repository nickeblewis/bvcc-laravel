<?php

namespace App\Filament\Support;

use App\Models\Booking;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

/**
 * Columns and row actions for bookings, shared by the all-bookings list, the
 * tab on an event and the dashboard.
 */
class BookingTable
{
    /**
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public static function eagerLoad(Builder $query): Builder
    {
        return $query->with(['event', 'attendees', 'payments']);
    }

    /**
     * @return list<TextColumn>
     */
    public static function columns(bool $withEvent = true): array
    {
        return [
            TextColumn::make('event.starts_at')
                ->label('Event')
                ->formatStateUsing(fn (Booking $record): string => $record->event->title)
                ->description(fn (Booking $record): string => $record->event->starts_at->format('D j M Y'))
                ->sortable()
                ->wrap()
                ->visible($withEvent),
            TextColumn::make('contact_name')
                ->label('Booked by')
                ->description(fn (Booking $record): ?string => $record->contact_email)
                ->searchable(['contact_name', 'contact_email']),
            TextColumn::make('people')
                ->state(fn (Booking $record): string => $record->attendees->pluck('name')->implode(', '))
                ->wrap(),
            TextColumn::make('status')->badge(),
            TextColumn::make('total')
                ->state(fn (Booking $record): string => Money::format($record->totalPence()))
                ->alignEnd(),
            TextColumn::make('balance')
                ->label('To pay')
                ->state(fn (Booking $record): string => Money::format(max(0, $record->balancePence())))
                ->color(fn (Booking $record): ?string => $record->balancePence() > 0 ? 'danger' : null)
                ->weight(fn (Booking $record): ?string => $record->balancePence() > 0 ? 'bold' : null)
                ->alignEnd(),
            TextColumn::make('last_chased_at')
                ->label('Reminded')
                ->since()
                ->placeholder('Never')
                ->toggleable(),
            TextColumn::make('created_at')->label('Booked')->date('j M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return list<Action>
     */
    public static function actions(): array
    {
        return [
            PaymentActions::record(),
            BookingActions::chase(),
            BookingActions::promote(),
            PaymentActions::refund(),
            BookingActions::cancel(),
        ];
    }
}

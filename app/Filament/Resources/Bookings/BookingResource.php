<?php

namespace App\Filament\Resources\Bookings;

use App\Enums\Area;
use App\Enums\BookingStatus;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Bookings\Pages\ManageBookings;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Support\BookingTable;
use App\Filament\Support\Staff;
use App\Models\Booking;
use App\Services\Bookings;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class BookingResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Programme';

    protected static ?int $navigationSort = 2;

    public static function area(): Area
    {
        return Area::Bookings;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Booking::toChase()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Awaiting payment';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => BookingTable::eagerLoad($query))
            ->defaultSort('created_at', 'desc')
            ->columns(BookingTable::columns())
            ->recordUrl(fn (Booking $record): string => EventResource::getUrl(
                Staff::can(Area::Events) ? 'edit' : 'register',
                ['record' => $record->event_id],
            ))
            ->filters([
                SelectFilter::make('status')->options(BookingStatus::class),
                Filter::make('to_chase')
                    ->label('Money owed on events going ahead')
                    ->query(fn (Builder $query) => Booking::whereToChase($query)),
                Filter::make('upcoming')
                    ->label('Upcoming events only')
                    ->default()
                    ->query(fn (Builder $query) => $query->whereHas('event', fn (Builder $e) => $e->where('starts_at', '>=', today()))),
            ])
            ->recordActions([ActionGroup::make(BookingTable::actions())])
            ->toolbarActions([
                BulkAction::make('chase')
                    ->label('Send reminders')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()
                    ->modalDescription('Emails each selected booking that still has money to pay.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $sent = $records->filter(fn ($booking): bool => $booking instanceof Booking
                            && $booking->status === BookingStatus::Pending
                            && app(Bookings::class)->chase($booking))->count();

                        Notification::make()->success()
                            ->title("{$sent} reminder".($sent === 1 ? '' : 's').' sent')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBookings::route('/')];
    }
}

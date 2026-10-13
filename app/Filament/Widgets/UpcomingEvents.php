<?php

namespace App\Filament\Widgets;

use App\Enums\Area;
use App\Enums\BookingStatus;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Support\Staff;
use App\Models\Event;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingEvents extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Staff::can(Area::Events) || Staff::can(Area::Bookings);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Coming up')
            ->query(Event::upcoming()
                ->whereNull('cancelled_at')
                ->with('venue')
                ->withCount([
                    'attendees',
                    'bookings as unpaid_count' => fn (Builder $q) => $q->where('status', BookingStatus::Pending),
                    'bookings as waiting_count' => fn (Builder $q) => $q->where('status', BookingStatus::Waiting),
                ]))
            ->columns([
                TextColumn::make('starts_at')->label('When')->dateTime('D j M, H:i'),
                TextColumn::make('title')->weight('medium')->wrap()
                    ->description(fn (Event $record): ?string => $record->venue?->name),
                TextColumn::make('attendees_count')
                    ->label('Booked')
                    ->formatStateUsing(fn (Event $record, $state): string => $record->capacity ? "{$state} / {$record->capacity}" : (string) $state)
                    ->color(fn (Event $record, $state): ?string => $record->capacity && $state >= $record->capacity ? 'warning' : null),
                TextColumn::make('unpaid_count')->label('Unpaid')->color(fn ($state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('waiting_count')->label('Waiting'),
                TextColumn::make('status')->badge(),
            ])
            ->recordUrl(fn (Event $record): string => EventResource::getUrl(Staff::can(Area::Events) ? 'edit' : 'register', ['record' => $record]))
            ->paginated([5, 10]);
    }
}

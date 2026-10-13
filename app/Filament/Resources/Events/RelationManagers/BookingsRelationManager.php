<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Enums\Area;
use App\Enums\BookingStatus;
use App\Filament\Support\BookingActions;
use App\Filament\Support\BookingTable;
use App\Filament\Support\Staff;
use App\Models\Event;
use Filament\Actions\ActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Staff::can(Area::Bookings);
    }

    public function table(Table $table): Table
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => BookingTable::eagerLoad($query))
            ->defaultSort('created_at')
            ->columns(BookingTable::columns(withEvent: false))
            ->filters([SelectFilter::make('status')->options(BookingStatus::class)])
            ->headerActions([BookingActions::create($event)])
            ->recordActions([ActionGroup::make(BookingTable::actions())])
            ->emptyStateHeading('Nobody is booked yet');
    }
}

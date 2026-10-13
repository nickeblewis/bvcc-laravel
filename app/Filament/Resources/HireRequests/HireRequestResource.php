<?php

namespace App\Filament\Resources\HireRequests;

use App\Enums\Area;
use App\Enums\HireRequestStatus;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\HireRequests\Pages\ManageHireRequests;
use App\Models\HireRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class HireRequestResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = HireRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 2;

    public static function area(): Area
    {
        return Area::Enquiries;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = HireRequest::where('status', HireRequestStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
                TextColumn::make('name')->searchable()->weight('medium')
                    ->description(fn (HireRequest $record): string => $record->email),
                TextColumn::make('collect_on')->label('Dates')
                    ->formatStateUsing(fn (HireRequest $record): string => $record->collect_on->format('j M').' to '.$record->return_on->format('j M Y')),
                TextColumn::make('items')->badge()->color('gray')
                    ->description(fn (HireRequest $record): ?string => $record->getAttribute('craft_type')),
                TextColumn::make('use_type')->label('Use')
                    ->formatStateUsing(fn (string $state): string => HireRequest::USE_TYPES[$state] ?? $state)
                    ->description(fn (HireRequest $record): ?string => $record->getAttribute('intended_use'))
                    ->wrap(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([SelectFilter::make('status')->options(HireRequestStatus::class)])
            ->recordActions([
                Action::make('reply')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->url(fn (HireRequest $record): string => 'mailto:'.$record->email.'?subject='.rawurlencode('Your equipment hire request')),
                ActionGroup::make([
                    ...array_map(
                        fn (HireRequestStatus $status) => Action::make($status->value)
                            ->label('Mark as '.strtolower($status->getLabel()))
                            ->visible(fn (HireRequest $record): bool => $record->status !== $status)
                            ->action(fn (HireRequest $record) => $record->update(['status' => $status])),
                        [HireRequestStatus::Approved, HireRequestStatus::Declined, HireRequestStatus::Returned],
                    ),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageHireRequests::route('/')];
    }
}

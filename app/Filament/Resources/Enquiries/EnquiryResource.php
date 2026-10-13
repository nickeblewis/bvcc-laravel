<?php

namespace App\Filament\Resources\Enquiries;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Enquiries\Pages\ManageEnquiries;
use App\Models\Enquiry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class EnquiryResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Enquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 1;

    public static function area(): Area
    {
        return Area::Enquiries;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Enquiry::whereNull('handled_at')->count();

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
                    ->description(fn (Enquiry $record): string => $record->email),
                TextColumn::make('role.title')->label('For')->badge()->color('gray'),
                TextColumn::make('message')->wrap()->lineClamp(3),
                TextColumn::make('handled_at')->label('Dealt with')->since()->placeholder('Not yet'),
            ])
            ->filters([
                TernaryFilter::make('handled_at')->label('Dealt with')->nullable()->default(false),
            ])
            ->recordActions([
                Action::make('reply')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->url(fn (Enquiry $record): string => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: your enquiry to '.config('site.short_name'))),
                Action::make('handled')
                    ->label('Mark as dealt with')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Enquiry $record): bool => $record->handled_at === null)
                    ->action(fn (Enquiry $record) => $record->update(['handled_at' => now()])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageEnquiries::route('/')];
    }
}

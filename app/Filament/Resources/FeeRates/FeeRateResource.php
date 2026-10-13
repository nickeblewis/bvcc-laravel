<?php

namespace App\Filament\Resources\FeeRates;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\FeeRates\Pages\ManageFeeRates;
use App\Filament\Support\MoneyInput;
use App\Models\FeeRate;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FeeRateResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = FeeRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyPound;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Club fees';

    protected static ?int $navigationSort = 2;

    public static function area(): Area
    {
        return Area::Settings;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            MoneyInput::make('amount_pence')
                ->label('Fee')
                ->helperText('Leave empty for "not applicable".'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->paginated(false)
            ->columns([
                TextColumn::make('fee_band')->label('Who'),
                TextColumn::make('fee_type')->label('For'),
                TextColumn::make('amount_pence')->label('Fee')
                    ->formatStateUsing(fn ($state): string => Money::format((int) $state))
                    ->placeholder('n/a'),
            ])
            ->recordActions([EditAction::make()->modalWidth('sm')]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFeeRates::route('/')];
    }
}

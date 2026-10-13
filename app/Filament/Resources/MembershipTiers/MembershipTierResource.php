<?php

namespace App\Filament\Resources\MembershipTiers;

use App\Enums\Area;
use App\Enums\FeeBand;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\MembershipTiers\Pages\ManageMembershipTiers;
use App\Filament\Support\ContentFields;
use App\Filament\Support\MoneyInput;
use App\Models\MembershipTier;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MembershipTierResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = MembershipTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function area(): Area
    {
        return Area::Settings;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ContentFields::title('name'),
            ContentFields::slug('#'),
            Select::make('fee_band')
                ->label('Session and event fees')
                ->helperText('Which column of the club fee table members on this tier pay.')
                ->options(FeeBand::class)
                ->required(),
            MoneyInput::make('initial_pence')->label('Annual or joining fee')->required()->default(0),
            MoneyInput::make('monthly_pence')->label('Monthly fee')->required()->default(0),
            TextInput::make('eligibility')->label('Who can have it')->maxLength(255),
            Textarea::make('description')->rows(2),
            Toggle::make('covers_household')->label('Covers a household (family tier)'),
            Toggle::make('on_sale')->label('Offered to new members')->default(true),
            Toggle::make('to_confirm')->label('Price needs checking with the committee'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->weight('medium')->wrap(),
                TextColumn::make('fee_band')->badge()->color('gray'),
                TextColumn::make('initial_pence')->label('Annual')->formatStateUsing(fn ($state): string => Money::format((int) $state)),
                TextColumn::make('monthly_pence')->label('Monthly')->formatStateUsing(fn ($state): string => $state ? Money::format((int) $state) : ''),
                TextColumn::make('memberships_count')->label('Memberships')->counts('memberships'),
                IconColumn::make('to_confirm')->label('To check')->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)->trueColor('warning')->falseIcon(null),
                IconColumn::make('on_sale')->label('On offer')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (MembershipTier $record): bool => $record->memberships()->doesntExist()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMembershipTiers::route('/')];
    }
}

<?php

namespace App\Filament\Resources\Venues;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Venues\Pages\CreateVenue;
use App\Filament\Resources\Venues\Pages\EditVenue;
use App\Filament\Resources\Venues\Pages\ListVenues;
use App\Filament\Support\ContentFields;
use App\Models\Venue;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VenueResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Venue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Programme';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function area(): Area
    {
        return Area::Events;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                ContentFields::title('name'),
                ContentFields::slug('/venues/'),
                TextInput::make('summary')->maxLength(255)->columnSpanFull(),
                TextInput::make('address')->maxLength(255),
                TextInput::make('town')->maxLength(255),
                TextInput::make('postcode')->maxLength(20),
                Toggle::make('is_listed')->label('Show on the venues page')->default(true)->inline(false),
                TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90)
                    ->helperText('Right-click the spot in Google Maps and copy the two numbers. Needed for the map pin.'),
                TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
                MarkdownEditor::make('description')->columnSpanFull(),
                Textarea::make('access_notes')
                    ->label('Parking and access')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
            ContentFields::photo('venues'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium')
                    ->description(fn (Venue $record): string => $record->fullAddress()),
                IconColumn::make('latitude')->label('Map pin')->boolean()->state(fn (Venue $record): bool => $record->latitude !== null),
                IconColumn::make('is_listed')->label('Listed')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVenues::route('/'),
            'create' => CreateVenue::route('/create'),
            'edit' => EditVenue::route('/{record}/edit'),
        ];
    }
}

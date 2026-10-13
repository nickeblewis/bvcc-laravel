<?php

namespace App\Filament\Resources\EventCategories;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\EventCategories\Pages\ManageEventCategories;
use App\Filament\Support\ContentFields;
use App\Models\EventCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class EventCategoryResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = EventCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Programme';

    protected static ?int $navigationSort = 5;

    public static function area(): Area
    {
        return Area::Events;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ContentFields::title('name'),
            ContentFields::slug('/events?category='),
            Select::make('colour')->options(EventCategory::COLOURS)->default('teal')->required(),
            TextInput::make('description')->maxLength(255),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->weight('medium'),
                TextColumn::make('colour')->badge()->color('gray'),
                TextColumn::make('events_count')->label('Events')->counts('events'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (EventCategory $record): bool => $record->slug !== EventCategory::SESSIONS && $record->events()->doesntExist()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageEventCategories::route('/')];
    }
}

<?php

namespace App\Filament\Resources\MediaItems;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\MediaItems\Pages\ManageMediaItems;
use App\Models\MediaItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class MediaItemResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = MediaItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Photos';

    protected static ?int $navigationSort = 8;

    public static function area(): Area
    {
        return Area::Content;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('file')->image()->disk('public')->directory('media')->imageEditor()->required(),
            TextInput::make('alt')
                ->label('Describe the photo')
                ->helperText('For people who cannot see it, and for search engines.')
                ->maxLength(255),
            TextInput::make('caption')->maxLength(255),
            TextInput::make('credit')->label('Photo credit')->maxLength(255),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('file')->label('Photo')->disk('public')->imageHeight(64),
                TextColumn::make('alt')->label('Description')->placeholder('No description yet')->wrap()->searchable(),
                IconColumn::make('is_imported')->label('From old site')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_imported')->label('From the old site'),
                TernaryFilter::make('alt')->label('Has a description')->nullable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMediaItems::route('/')];
    }
}

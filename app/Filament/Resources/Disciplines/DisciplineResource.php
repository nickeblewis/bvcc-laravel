<?php

namespace App\Filament\Resources\Disciplines;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Disciplines\Pages\CreateDiscipline;
use App\Filament\Resources\Disciplines\Pages\EditDiscipline;
use App\Filament\Resources\Disciplines\Pages\ListDisciplines;
use App\Filament\Support\ContentFields;
use App\Models\Discipline;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
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

class DisciplineResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Discipline::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function area(): Area
    {
        return Area::Content;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                ContentFields::title('name'),
                ContentFields::slug('/paddle/'),
                TextInput::make('summary')
                    ->helperText('One sentence, shown on cards.')
                    ->maxLength(255)
                    ->columnSpanFull(),
                ContentFields::facts()->columnSpanFull(),
                MarkdownEditor::make('body')
                    ->helperText('Write headings as questions people ask ("How do I start?"). It helps readers and search engines.')
                    ->columnSpanFull(),
                TextInput::make('video_url')->label('Vimeo or YouTube link')->url()->maxLength(255),
                TextInput::make('contact_email')->email()->maxLength(255),
                ContentFields::metaDescription()->columnSpanFull(),
                Toggle::make('is_published')->label('Published')->default(true),
                TextInput::make('sort')->label('Order')->numeric()->default(0),
            ]),
            ContentFields::photo('disciplines'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('summary')->wrap()->color('gray'),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisciplines::route('/'),
            'create' => CreateDiscipline::route('/create'),
            'edit' => EditDiscipline::route('/{record}/edit'),
        ];
    }
}

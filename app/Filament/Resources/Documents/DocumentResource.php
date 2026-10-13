<?php

namespace App\Filament\Resources\Documents;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Documents\Pages\ManageDocuments;
use App\Models\Document;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DocumentResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperClip;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 4;

    public static function area(): Area
    {
        return Area::Content;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            Select::make('category')->options(Document::CATEGORIES)->required(),
            FileUpload::make('file')
                ->disk('public')
                ->directory('documents')
                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                ->maxSize(20 * 1024)
                ->helperText('PDF is best: it opens on any phone.')
                ->required(),
            DatePicker::make('document_date')->label('Date of the document')->maxDate(today()),
            Toggle::make('is_public')->label('Show on the website')->default(true),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('document_date', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->wrap()->weight('medium'),
                TextColumn::make('category')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state): string => Document::CATEGORIES[$state] ?? $state),
                TextColumn::make('document_date')->label('Dated')->date('j M Y')->sortable(),
                IconColumn::make('is_public')->label('Public')->boolean(),
            ])
            ->filters([SelectFilter::make('category')->options(Document::CATEGORIES)])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDocuments::route('/')];
    }
}

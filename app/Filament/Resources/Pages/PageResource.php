<?php

namespace App\Filament\Resources\Pages;

use App\Enums\Area;
use App\Enums\PageSection;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\ContentFields;
use App\Models\Faq;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class PageResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function area(): Area
    {
        return Area::Content;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                ContentFields::title()->columnSpanFull(),
                Select::make('section')->options(PageSection::class)->required()->live(),
                ContentFields::slug('/', unique: 'section'),
                TextInput::make('summary')
                    ->helperText('One or two sentences that answer "what is this page?". Shown under the title.')
                    ->maxLength(255)
                    ->columnSpanFull(),
                ContentFields::facts()->columnSpanFull(),
                MarkdownEditor::make('body')
                    ->helperText('Write headings as questions people ask. It helps readers and search engines.')
                    ->columnSpanFull(),
                Select::make('faq_category')
                    ->label('Show FAQs from')
                    ->options(Faq::CATEGORIES)
                    ->placeholder('None'),
                DatePicker::make('reviewed_on')
                    ->label('Last reviewed')
                    ->helperText('Shown on the page so readers know it is current.')
                    ->maxDate(today()),
                ContentFields::metaDescription()->columnSpanFull(),
                Toggle::make('is_published')->label('Published')->default(true),
                Toggle::make('to_confirm')
                    ->label('Needs checking with the committee')
                    ->helperText('A reminder for staff. Not shown on the site.'),
            ]),
            ContentFields::photo('pages'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->searchable()->weight('medium')
                    ->description(fn (Page $record): string => $record->path()),
                TextColumn::make('section')->badge()->color('gray'),
                TextColumn::make('reviewed_on')->label('Reviewed')->date('j M Y')->placeholder('Never')->sortable(),
                IconColumn::make('to_confirm')->label('To check')->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)->trueColor('warning')->falseIcon(null),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->filters([
                SelectFilter::make('section')->options(PageSection::class),
                TernaryFilter::make('to_confirm')->label('Needs checking'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}

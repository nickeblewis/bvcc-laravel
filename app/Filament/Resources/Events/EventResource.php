<?php

namespace App\Filament\Resources\Events;

use App\Enums\Area;
use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\FeeBand;
use App\Enums\FeeType;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\EventRegister;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\RelationManagers\BookingsRelationManager;
use App\Filament\Support\MoneyInput;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use App\Services\Bookings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

class EventResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Programme';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function area(): Area
    {
        return Area::Events;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('What')->columns(2)->schema([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Select::make('event_category_id')
                    ->label('Category')
                    ->options(fn (): array => EventCategory::orderBy('sort')->pluck('name', 'id')->all())
                    ->required(),
                Select::make('discipline_id')
                    ->relationship('discipline', 'name')
                    ->placeholder('Any or none'),
                TextInput::make('summary')
                    ->helperText('One sentence. Shown on event cards and used as the search-engine description.')
                    ->maxLength(255)
                    ->columnSpanFull(),
                MarkdownEditor::make('description')->columnSpanFull(),
            ]),
            Section::make('When and where')->columns(2)->schema([
                DateTimePicker::make('starts_at')->required()->seconds(false),
                DateTimePicker::make('ends_at')->seconds(false)->afterOrEqual('starts_at'),
                Toggle::make('all_day')->label('All day, or more than one day')->columnSpanFull(),
                Select::make('venue_id')->relationship('venue', 'name')->searchable()->preload(),
                Select::make('leader_id')
                    ->label('Leader')
                    ->helperText('A staff login. The leader can see the register, with emergency and medical details, for this event only.')
                    ->options(fn (): array => User::whereNotNull('role')->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
            ]),
            Section::make('Booking')->columns(2)->schema([
                TextInput::make('capacity')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Leave empty for no limit. Extra bookings go on the waiting list.'),
                DateTimePicker::make('booking_closes_at')->label('Booking cut-off')->seconds(false),
                Select::make('grade')
                    ->label('River grade')
                    ->options([1 => 'Grade 1', 2 => 'Grade 2', 3 => 'Grade 3', 4 => 'Grade 4', 5 => 'Grade 5'])
                    ->placeholder('Not whitewater'),
                TextInput::make('suitability')
                    ->placeholder('All levels, age 8 and up')
                    ->maxLength(255),
            ]),
            Section::make('Fees')->columns(2)->schema([
                Select::make('fee_type')
                    ->label('Club fee')
                    ->options(FeeType::class)
                    ->default(FeeType::Session->value)
                    ->required()
                    ->live()
                    ->helperText('Standard types use the club fee table.'),
                TextInput::make('extra_costs')
                    ->placeholder('Accommodation is charged separately.')
                    ->maxLength(255),
                ...array_map(
                    fn (FeeBand $band) => MoneyInput::make("custom_prices.{$band->value}")
                        ->label($band->getLabel())
                        ->visible(fn ($get): bool => in_array($get('fee_type'), [FeeType::Custom, FeeType::Custom->value], true)),
                    FeeBand::cases(),
                ),
            ]),
            Section::make('Publishing')->columns(2)->schema([
                Select::make('status')
                    ->options([EventStatus::Draft->value => 'Draft', EventStatus::Published->value => 'Published'])
                    ->default(EventStatus::Draft->value)
                    ->required()
                    ->disabled(fn (?Event $record): bool => (bool) $record?->isCancelled())
                    ->helperText('Drafts are only visible here.'),
                TextInput::make('slug')
                    ->label('Web address')
                    ->prefix('/events/')
                    ->helperText('Made from the title and date if left empty.')
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                FileUpload::make('hero_image')->label('Photo')->image()->disk('public')->directory('events')->imageEditor(),
                Textarea::make('hero_alt')->label('Describe the photo')->helperText('For people who cannot see it.')->rows(2),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['category', 'venue'])
                ->withCount([
                    'attendees',
                    'bookings as unpaid_count' => fn (Builder $q) => $q->where('status', BookingStatus::Pending),
                    'bookings as waiting_count' => fn (Builder $q) => $q->where('status', BookingStatus::Waiting),
                ]))
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('starts_at')->label('When')->dateTime('D j M Y, H:i')->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->weight('medium')
                    ->wrap()
                    ->description(fn (Event $record): ?string => $record->venue?->name),
                TextColumn::make('category.name')->badge()->color('gray'),
                TextColumn::make('attendees_count')
                    ->label('Booked')
                    ->formatStateUsing(fn (Event $record, $state): string => $record->capacity ? "{$state} / {$record->capacity}" : (string) $state)
                    ->color(fn (Event $record, $state): ?string => $record->capacity && $state >= $record->capacity ? 'warning' : null)
                    ->description(fn (Event $record): ?string => $record->getAttribute('waiting_count') ? $record->getAttribute('waiting_count').' waiting' : null),
                TextColumn::make('unpaid_count')
                    ->label('Unpaid')
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->default()
                    ->query(fn (Builder $query) => $query->where('starts_at', '>=', today())),
                SelectFilter::make('event_category_id')
                    ->label('Category')
                    ->options(fn (): array => EventCategory::orderBy('sort')->pluck('name', 'id')->all()),
                SelectFilter::make('status')->options(EventStatus::class),
            ])
            ->recordActions([
                Action::make('register')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->url(fn (Event $record): string => static::getUrl('register', ['record' => $record])),
                ActionGroup::make([
                    EditAction::make(),
                    static::duplicateAction(),
                    static::cancelAction(),
                ]),
            ]);
    }

    public static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->modalWidth('md')
            ->modalDescription('Copies everything except the bookings. The copy starts as a draft.')
            ->schema([
                DateTimePicker::make('starts_at')->label('New start')->required()->seconds(false),
            ])
            ->action(function (array $data, Event $record): void {
                $copy = $record->replicate(['slug', 'series_id', 'cancelled_at', 'cancel_reason', 'attendees_count', 'unpaid_count', 'waiting_count']);
                $shift = (int) $record->starts_at->diffInSeconds(Carbon::parse($data['starts_at']));

                $copy->fill([
                    'starts_at' => $record->starts_at->addSeconds($shift),
                    'ends_at' => $record->ends_at?->addSeconds($shift),
                    'booking_closes_at' => $record->booking_closes_at?->addSeconds($shift),
                    'status' => EventStatus::Draft,
                ])->save();

                Notification::make()->success()->title('Event duplicated as a draft')->send();
            });
    }

    public static function cancelAction(): Action
    {
        return Action::make('cancelEvent')
            ->label('Cancel event')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Event $record): bool => ! $record->isCancelled())
            ->modalHeading('Cancel this event?')
            ->modalDescription('Everyone with a booking or on the waiting list is emailed. The event stays on the website, marked as cancelled. Bookings are kept so you can record refunds.')
            ->schema([
                Textarea::make('reason')
                    ->label('Message to the people booked')
                    ->placeholder('The river is too low to paddle.')
                    ->rows(3),
            ])
            ->action(function (array $data, Event $record): void {
                $emailed = app(Bookings::class)->cancelEvent($record, $data['reason'] ?? null);

                Notification::make()->success()
                    ->title('Event cancelled')
                    ->body("{$emailed} ".($emailed === 1 ? 'person' : 'people').' emailed.')
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [BookingsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
            'register' => EventRegister::route('/{record}/register'),
        ];
    }
}

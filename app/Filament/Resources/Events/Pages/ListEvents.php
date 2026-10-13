<?php

namespace App\Filament\Resources\Events\Pages;

use App\Enums\EventStatus;
use App\Enums\FeeType;
use App\Filament\Resources\Events\EventResource;
use App\Models\EventCategory;
use App\Models\Venue;
use App\Services\EventSeries;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->seriesAction(),
            CreateAction::make(),
        ];
    }

    /**
     * Plan a season of weekly sessions in one go.
     */
    private function seriesAction(): Action
    {
        return Action::make('createSeries')
            ->label('Create weekly series')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->modalWidth('2xl')
            ->modalDescription('Creates one event a week between two dates. You can edit or cancel individual dates afterwards.')
            ->schema([
                TextInput::make('title')->required()->placeholder('Lido session')->maxLength(255),
                TextInput::make('summary')->maxLength(255),
                Grid::make(2)->schema([
                    Select::make('event_category_id')
                        ->label('Category')
                        ->options(fn (): array => EventCategory::orderBy('sort')->pluck('name', 'id')->all())
                        ->default(fn (): ?int => EventCategory::where('slug', EventCategory::SESSIONS)->value('id'))
                        ->required(),
                    Select::make('venue_id')
                        ->label('Venue')
                        ->options(fn (): array => Venue::orderBy('sort')->pluck('name', 'id')->all()),
                    Select::make('weekday')
                        ->options(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])
                        ->default(3)
                        ->required(),
                    TextInput::make('capacity')->numeric()->minValue(1),
                    TimePicker::make('start_time')->seconds(false)->default('19:00')->required(),
                    TimePicker::make('end_time')->seconds(false)->default('20:30')->required()->after('start_time'),
                    DatePicker::make('from')->label('First week')->required()->default(today()),
                    DatePicker::make('to')->label('Last week')->required()->afterOrEqual('from')
                        ->maxDate(today()->addMonths(18)),
                    Select::make('fee_type')
                        ->label('Club fee')
                        ->options(FeeType::class)
                        ->default(FeeType::Session->value)
                        ->required(),
                    Select::make('status')
                        ->options([EventStatus::Draft->value => 'Draft', EventStatus::Published->value => 'Published'])
                        ->default(EventStatus::Draft->value)
                        ->required(),
                ]),
            ])
            ->action(function (array $data): void {
                $events = app(EventSeries::class)->generate(
                    attributes: [
                        'title' => $data['title'],
                        'summary' => $data['summary'] ?? null,
                        'event_category_id' => $data['event_category_id'],
                        'venue_id' => $data['venue_id'] ?? null,
                        'capacity' => $data['capacity'] ?? null,
                        'fee_type' => $data['fee_type'],
                        'status' => $data['status'],
                    ],
                    weekday: (int) $data['weekday'],
                    from: $data['from'],
                    to: $data['to'],
                    startTime: substr($data['start_time'], 0, 5),
                    endTime: substr($data['end_time'], 0, 5),
                );

                Notification::make()->success()->title($events->count().' events created')->send();
            });
    }
}

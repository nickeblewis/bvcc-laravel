<?php

namespace App\Filament\Support;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Member;
use App\Services\Bookings;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

/**
 * The things staff do to a booking, shared by the event's bookings tab, the
 * all-bookings list and the dashboard.
 */
class BookingActions
{
    /** @var array<string, string> */
    public const WAIVERS = [
        'Volunteer' => 'Volunteering at this event',
        'Whitewater award' => 'Holds a qualifying whitewater award',
        'Other' => 'Other (agreed by the committee)',
    ];

    /**
     * "New booking". Pass the event when it is already known (the event's
     * bookings tab); otherwise the form asks for it.
     */
    public static function create(?Event $event = null): Action
    {
        return Action::make('createBooking')
            ->label('New booking')
            ->icon(Heroicon::OutlinedPlus)
            ->modalWidth('3xl')
            ->schema([
                Select::make('event_id')
                    ->label('Event')
                    ->options(fn (): array => Event::upcoming()->whereNull('cancelled_at')->limit(200)->get()
                        ->mapWithKeys(fn (Event $e) => [$e->id => $e->starts_at->format('D j M Y').': '.$e->title])->all())
                    ->searchable()
                    ->required()
                    ->visible($event === null),
                Section::make('Who is booking?')
                    ->description('Pick someone from the members list, or type a guest\'s contact details.')
                    ->schema([
                        self::memberSelect('member_id')->label('Member')->live(),
                        Grid::make(3)->schema([
                            TextInput::make('contact_name')->label('Guest name')->requiredWithout('member_id'),
                            TextInput::make('contact_email')->label('Email')->email(),
                            TextInput::make('contact_phone')->label('Phone')->tel(),
                        ])->hidden(fn ($get): bool => filled($get('member_id'))),
                    ]),
                Repeater::make('attendees')
                    ->label('Who is coming?')
                    ->helperText('Leave this empty to book just the member above. Prices are worked out from each person\'s membership.')
                    ->schema([
                        self::memberSelect('member_id')->label('Member'),
                        TextInput::make('name')->label('or guest name'),
                        Select::make('waived_reason')->label('Waive the fee?')->options(static::WAIVERS)->placeholder('No, charge the usual fee'),
                    ])
                    ->columns(3)
                    ->defaultItems(0)
                    ->addActionLabel('Add a person'),
                Textarea::make('notes')->rows(2),
            ])
            ->action(function (array $data) use ($event): void {
                $event ??= Event::query()->whereKey($data['event_id'])->firstOrFail();

                $attendees = array_values(array_filter(
                    (array) ($data['attendees'] ?? []),
                    fn (array $a): bool => filled($a['member_id'] ?? null) || filled($a['name'] ?? null),
                ));

                if ($attendees === []) {
                    $attendees = filled($data['member_id'] ?? null)
                        ? [['member_id' => (int) $data['member_id']]]
                        : [['name' => $data['contact_name'] ?? null]];
                }

                try {
                    $booking = app(Bookings::class)->create($event, $data, $attendees);
                } catch (DomainException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    return;
                }

                $booking->status === BookingStatus::Waiting
                    ? Notification::make()->warning()->title('Added to the waiting list')->body('The event does not have enough places left.')->send()
                    : Notification::make()->success()->title('Booking added')->send();
            });
    }

    public static function chase(): Action
    {
        return Action::make('chase')
            ->label('Send reminder')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('warning')
            ->visible(fn (Booking $record): bool => $record->status === BookingStatus::Pending && filled($record->contact_email))
            ->requiresConfirmation()
            ->modalDescription(fn (Booking $record): string => "Email {$record->contact_email} to ask for payment?"
                .($record->last_chased_at ? ' Last reminded '.$record->last_chased_at->diffForHumans().'.' : ''))
            ->action(function (Booking $record): void {
                app(Bookings::class)->chase($record);

                Notification::make()->success()->title('Reminder sent')->send();
            });
    }

    public static function promote(): Action
    {
        return Action::make('promote')
            ->label('Give a place')
            ->icon(Heroicon::OutlinedArrowUpCircle)
            ->color('info')
            ->visible(fn (Booking $record): bool => $record->status === BookingStatus::Waiting)
            ->action(function (Booking $record): void {
                try {
                    app(Bookings::class)->promote($record);
                } catch (DomainException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('Moved off the waiting list')->send();
            });
    }

    public static function cancel(): Action
    {
        return Action::make('cancelBooking')
            ->label('Cancel')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Booking $record): bool => $record->status !== BookingStatus::Cancelled)
            ->modalHeading('Cancel this booking?')
            ->modalDescription('The places are released. Any payments stay on the booking until you record a refund.')
            ->schema([TextInput::make('reason')->maxLength(255)])
            ->action(function (array $data, Booking $record): void {
                app(Bookings::class)->cancel($record, $data['reason'] ?? null);

                Notification::make()->success()->title('Booking cancelled')->send();
            });
    }

    private static function memberSelect(string $name): Select
    {
        return Select::make($name)
            ->searchable()
            ->getSearchResultsUsing(fn (string $search): array => Member::query()
                ->search($search)
                ->limit(20)->get()
                ->mapWithKeys(fn (Member $m) => [$m->id => "{$m->name} ({$m->feeBand()->getLabel()})"])->all())
            ->getOptionLabelUsing(fn ($value): ?string => Member::query()->whereKey($value)->first()?->name);
    }
}

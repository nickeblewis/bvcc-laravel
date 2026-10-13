<?php

namespace App\Filament\Resources\Events\Pages;

use App\Enums\Area;
use App\Enums\BookingStatus;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Support\Csv;
use App\Filament\Support\Staff;
use App\Models\BookingAttendee;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The register for one event: who is coming, whether they have paid, and a
 * tick box for who turned up. Built to be used on a phone at the waterside
 * and to print cleanly. Emergency and medical details are shown only to
 * welfare staff and to this event's leader.
 */
class EventRegister extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = EventResource::class;

    protected string $view = 'filament.event-register';

    /**
     * Welfare staff can open a register without being able to edit events.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function canAccess(array $parameters = []): bool
    {
        return Staff::can(Area::Events) || Staff::can(Area::Bookings);
    }

    public static function authorizeResourceAccess(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function event(): Event
    {
        /** @var Event $event */
        $event = $this->getRecord();

        return $event;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Register: '.$this->event()->title;
    }

    public function getSubheading(): string
    {
        $event = $this->event();

        return $event->starts_at->format('l j F Y, H:i').($event->venue ? ' at '.$event->venue->name : '');
    }

    public function showsMedical(): bool
    {
        return (bool) Staff::user()?->canSeeMedical($this->event());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->alpineClickHandler('window.print()'),
            Action::make('csv')
                ->label('Download CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->csv()),
            Action::make('edit')
                ->label('Bookings')
                ->icon(Heroicon::OutlinedTicket)
                ->visible(fn (): bool => Staff::can(Area::Events))
                ->url(fn (): string => EventResource::getUrl('edit', ['record' => $this->event()])),
        ];
    }

    /**
     * @return Builder<BookingAttendee>
     */
    private function attendees(): Builder
    {
        return BookingAttendee::query()
            ->whereHas('booking', fn (Builder $booking) => $booking
                ->where('event_id', $this->event()->id)
                ->whereIn('status', BookingStatus::holdingAPlace()))
            ->with(['booking.payments', 'booking.attendees', 'member']);
    }

    public function table(Table $table): Table
    {
        $medical = $this->showsMedical();

        return $table
            ->query($this->attendees())
            ->defaultSort('name')
            ->paginated(false)
            ->columns([
                ToggleColumn::make('attended_at')
                    ->label('Here')
                    ->getStateUsing(fn (BookingAttendee $record): bool => $record->attended_at !== null)
                    ->updateStateUsing(fn (BookingAttendee $record, bool $state) => $record->update(['attended_at' => $state ? now() : null])),
                TextColumn::make('name')
                    ->weight('medium')
                    ->description(fn (BookingAttendee $record): ?string => ($age = $record->member?->age()) !== null && $age < 18 ? "Age {$age}" : null),
                IconColumn::make('paid')
                    ->state(fn (BookingAttendee $record): bool => $record->booking->status === BookingStatus::Confirmed)
                    ->boolean(),
                TextColumn::make('fee_band')->label('Fee')->toggleable(),
                TextColumn::make('emergency')
                    ->label('Emergency contact')
                    ->state(fn (BookingAttendee $record): string => collect($record->member?->emergency_contacts)
                        ->map(fn (array $c): string => trim(($c['name'] ?? '').' '.($c['phone'] ?? '')))
                        ->implode('; ') ?: 'Not on file')
                    ->wrap()
                    ->visible($medical),
                TextColumn::make('medical')
                    ->state(fn (BookingAttendee $record): ?string => $record->member?->medical_notes)
                    ->placeholder('None on file')
                    ->wrap()
                    ->color('danger')
                    ->visible($medical),
            ])
            ->emptyStateHeading('Nobody is booked yet');
    }

    private function csv(): StreamedResponse
    {
        $medical = $this->showsMedical();
        $headings = ['Name', 'Age', 'Fee band', 'Paid', 'Attended', 'Booked by', 'Phone'];

        if ($medical) {
            array_push($headings, 'Emergency contacts', 'Medical notes');
        }

        $rows = $this->attendees()->orderBy('name')->get()->map(function (BookingAttendee $a) use ($medical): array {
            $row = [
                $a->name,
                $a->member?->age(),
                $a->fee_band->getLabel(),
                $a->booking->status === BookingStatus::Confirmed ? 'Yes' : 'No',
                $a->attended_at ? 'Yes' : '',
                $a->booking->contact_name,
                $a->booking->getAttribute('contact_phone'),
            ];

            if ($medical) {
                $row[] = collect($a->member?->emergency_contacts)
                    ->map(fn (array $c): string => trim(($c['name'] ?? '').' ('.($c['relationship'] ?? '').') '.($c['phone'] ?? '')))
                    ->implode('; ');
                $row[] = $a->member?->medical_notes;
            }

            return $row;
        });

        return Csv::download('register-'.$this->event()->slug.'.csv', $headings, $rows);
    }
}

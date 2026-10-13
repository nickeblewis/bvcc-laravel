<?php

namespace App\Filament\Pages;

use App\Enums\Area;
use App\Enums\EventStatus;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * The programme at a glance: a month grid, or the whole year as twelve lists
 * for planning ahead.
 */
class Calendar extends Page
{
    use RestrictedToArea;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'Programme';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.calendar';

    #[Url]
    public string $mode = 'month';

    #[Url]
    public ?string $month = null;

    /** @var array<string, string> hex colours for the category colour names */
    public const COLOURS = [
        'teal' => '#0d9488',
        'blue' => '#2563eb',
        'gold' => '#b45309',
        'coral' => '#e11d48',
        'green' => '#15803d',
        'violet' => '#7c3aed',
        'slate' => '#475569',
    ];

    public static function area(): Area
    {
        return Area::Events;
    }

    public function start(): CarbonImmutable
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $this->month) ? $this->month : today()->format('Y-m');
        $start = CarbonImmutable::parse($month.'-01');

        return $this->mode === 'year' ? $start->startOfYear() : $start;
    }

    public function shift(int $steps): void
    {
        $this->month = ($this->mode === 'year'
            ? $this->start()->addYears($steps)
            : $this->start()->addMonths($steps))->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->month = today()->format('Y-m');
    }

    public function show(string $mode): void
    {
        $this->mode = $mode === 'year' ? 'year' : 'month';
    }

    public function getHeading(): string
    {
        return $this->mode === 'year' ? $this->start()->format('Y') : $this->start()->format('F Y');
    }

    /**
     * @return Collection<(int|string), Collection<int, Event>> events keyed by "Y-m-d"
     */
    public function events(): Collection
    {
        $start = $this->start();
        $end = $this->mode === 'year' ? $start->endOfYear() : $start->endOfMonth();

        return Event::query()
            ->whereBetween('starts_at', [$start->startOfWeek(), $end->endOfWeek()])
            ->with('category')
            ->withCount('attendees')
            ->orderBy('starts_at')
            ->get()
            ->toBase()
            ->groupBy(fn (Event $event): string => $event->starts_at->format('Y-m-d'));
    }

    /**
     * The weeks to draw for a month, Monday first, padded with the days
     * either side.
     *
     * @return list<list<CarbonImmutable>>
     */
    public function weeks(CarbonImmutable $month): array
    {
        $weeks = [];

        for ($day = $month->startOfMonth()->startOfWeek(); $day->lte($month->endOfMonth()); $day = $day->addWeek()) {
            $weeks[] = array_map(fn (int $i) => $day->addDays($i), range(0, 6));
        }

        return $weeks;
    }

    public function colour(Event $event): string
    {
        return $event->status === EventStatus::Published
            ? (static::COLOURS[$event->category->colour] ?? static::COLOURS['slate'])
            : '#9ca3af';
    }

    public function editUrl(Event $event): string
    {
        return EventResource::getUrl('edit', ['record' => $event]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new')
                ->label('New event')
                ->icon(Heroicon::OutlinedPlus)
                ->url(EventResource::getUrl('create')),
        ];
    }
}

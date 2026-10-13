<?php

namespace App\Services;

use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EventSeries
{
    /**
     * Create one event a week between two dates, all sharing a series id.
     *
     * @param  array<string, mixed>  $attributes  event attributes shared by every date (no starts_at or ends_at)
     * @param  int  $weekday  0 (Sunday) to 6 (Saturday)
     * @param  string  $startTime  "19:00"
     * @param  string  $endTime  "20:30"
     * @return Collection<int, Event>
     */
    public function generate(array $attributes, int $weekday, string $from, string $to, string $startTime, string $endTime): Collection
    {
        $seriesId = (string) Str::uuid();
        $date = CarbonImmutable::parse($from)->startOfDay();
        $last = CarbonImmutable::parse($to)->endOfDay();

        while ($date->dayOfWeek !== $weekday) {
            $date = $date->addDay();
        }

        $events = collect();

        for (; $date->lte($last); $date = $date->addWeek()) {
            $events->push(Event::create([
                ...$attributes,
                'series_id' => $seriesId,
                'starts_at' => $date->setTimeFromTimeString($startTime),
                'ends_at' => $date->setTimeFromTimeString($endTime),
            ]));
        }

        return $events;
    }
}

@php
    $events = $this->events();
    $start = $this->start();
    $today = today()->format('Y-m-d');
@endphp

<x-filament-panels::page>
    <div class="bvcc-cal">
        <div class="bvcc-cal-bar">
            <div class="bvcc-cal-group">
                <button type="button" wire:click="shift(-1)" aria-label="Previous">&larr;</button>
                <button type="button" wire:click="goToToday">Today</button>
                <button type="button" wire:click="shift(1)" aria-label="Next">&rarr;</button>
            </div>
            <div class="bvcc-cal-group">
                <button type="button" wire:click="show('month')" @class(['is-on' => $this->mode === 'month'])>Month</button>
                <button type="button" wire:click="show('year')" @class(['is-on' => $this->mode === 'year'])>Year</button>
            </div>
        </div>

        @if ($this->mode === 'month')
            {{-- Grid on wide screens, agenda list on phones. --}}
            <div class="bvcc-cal-grid" role="grid" aria-label="{{ $start->format('F Y') }}">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $name)
                    <div class="bvcc-cal-dow" role="columnheader">{{ $name }}</div>
                @endforeach

                @foreach ($this->weeks($start) as $week)
                    @foreach ($week as $day)
                        @php $key = $day->format('Y-m-d'); @endphp
                        <div role="gridcell" @class(['bvcc-cal-day', 'is-out' => $day->month !== $start->month, 'is-today' => $key === $today])>
                            <span class="bvcc-cal-num">{{ $day->day }}</span>
                            @foreach ($events->get($key, []) as $event)
                                <a href="{{ $this->editUrl($event) }}" class="bvcc-cal-chip" style="--c: {{ $this->colour($event) }}"
                                   title="{{ $event->title }} ({{ $event->status->getLabel() }})">
                                    <span @class(['is-cancelled' => $event->isCancelled()])>
                                        {{ $event->all_day ? '' : $event->starts_at->format('H:i') }} {{ $event->title }}
                                    </span>
                                    <small>{{ $event->attendees_count }}{{ $event->capacity ? '/'.$event->capacity : '' }}</small>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                @endforeach
            </div>

            <ul class="bvcc-cal-agenda">
                @forelse ($events->filter(fn ($list, $key) => str_starts_with($key, $start->format('Y-m'))) as $key => $list)
                    @foreach ($list as $event)
                        <li>
                            <a href="{{ $this->editUrl($event) }}" style="--c: {{ $this->colour($event) }}">
                                <strong>{{ $event->starts_at->format('D j') }}</strong>
                                <span @class(['is-cancelled' => $event->isCancelled()])>{{ $event->title }}</span>
                                <small>{{ $event->attendees_count }}{{ $event->capacity ? '/'.$event->capacity : '' }} booked</small>
                            </a>
                        </li>
                    @endforeach
                @empty
                    <li class="bvcc-cal-empty">Nothing planned this month.</li>
                @endforelse
            </ul>
        @else
            <div class="bvcc-cal-year">
                @foreach (range(0, 11) as $offset)
                    @php
                        $month = $start->addMonths($offset);
                        $inMonth = $events->filter(fn ($list, $key) => str_starts_with($key, $month->format('Y-m')))->flatten(1);
                    @endphp
                    <section>
                        <h3>
                            <button type="button" wire:click="$set('month', '{{ $month->format('Y-m') }}'); $wire.show('month')">{{ $month->format('F') }}</button>
                            <small>{{ $inMonth->count() }} {{ Str::plural('event', $inMonth->count()) }}</small>
                        </h3>
                        <ul>
                            @forelse ($inMonth as $event)
                                <li>
                                    <a href="{{ $this->editUrl($event) }}" style="--c: {{ $this->colour($event) }}">
                                        <strong>{{ $event->starts_at->format('D j') }}</strong>
                                        <span @class(['is-cancelled' => $event->isCancelled()])>{{ $event->title }}</span>
                                    </a>
                                </li>
                            @empty
                                <li class="bvcc-cal-empty">Nothing planned</li>
                            @endforelse
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif

        <p class="bvcc-cal-key">
            @foreach (\App\Models\EventCategory::orderBy('sort')->get() as $category)
                <span style="--c: {{ self::COLOURS[$category->colour] ?? self::COLOURS['slate'] }}">{{ $category->name }}</span>
            @endforeach
            <span style="--c: #9ca3af">Draft or cancelled</span>
        </p>
    </div>

    <style>
        .bvcc-cal { --line: rgb(0 0 0 / .1); --soft: rgb(0 0 0 / .04); font-size: .8125rem; }
        .dark .bvcc-cal { --line: rgb(255 255 255 / .12); --soft: rgb(255 255 255 / .05); }
        .bvcc-cal-bar { display: flex; justify-content: space-between; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .bvcc-cal-group { display: inline-flex; border: 1px solid var(--line); border-radius: .5rem; overflow: hidden; }
        .bvcc-cal-group button { padding: .5rem .875rem; min-height: 2.5rem; font-weight: 500; }
        .bvcc-cal-group button + button { border-left: 1px solid var(--line); }
        .bvcc-cal-group button:hover, .bvcc-cal-group button.is-on { background: var(--soft); }
        .bvcc-cal-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border: 1px solid var(--line); border-radius: .75rem; overflow: hidden; }
        .bvcc-cal-dow { padding: .5rem; font-weight: 600; text-align: center; background: var(--soft); }
        .bvcc-cal-day { min-height: 6.5rem; padding: .25rem; border-top: 1px solid var(--line); border-left: 1px solid var(--line); display: flex; flex-direction: column; gap: 2px; }
        .bvcc-cal-day:nth-child(7n + 1) { border-left: 0; }
        .bvcc-cal-day.is-out { opacity: .45; }
        .bvcc-cal-num { align-self: flex-end; padding: 0 .375rem; border-radius: 999px; }
        .bvcc-cal-day.is-today .bvcc-cal-num { background: #0d9488; color: #fff; font-weight: 700; }
        .bvcc-cal-chip { display: flex; justify-content: space-between; gap: .25rem; padding: .125rem .375rem; border-radius: .25rem; border-left: 3px solid var(--c); background: color-mix(in srgb, var(--c) 14%, transparent); line-height: 1.3; }
        .bvcc-cal-chip:hover { background: color-mix(in srgb, var(--c) 28%, transparent); }
        .bvcc-cal-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .bvcc-cal-chip small { opacity: .7; flex: none; }
        .bvcc-cal .is-cancelled { text-decoration: line-through; }
        .bvcc-cal-agenda { display: none; }
        .bvcc-cal-agenda a, .bvcc-cal-year a { display: flex; gap: .5rem; align-items: baseline; padding: .5rem .625rem; border-left: 3px solid var(--c); }
        .bvcc-cal-agenda a:hover, .bvcc-cal-year a:hover { background: var(--soft); }
        .bvcc-cal-agenda strong, .bvcc-cal-year strong { flex: none; width: 3.25rem; }
        .bvcc-cal-agenda small { margin-left: auto; opacity: .7; flex: none; }
        .bvcc-cal-agenda li + li, .bvcc-cal-year li + li { border-top: 1px solid var(--line); }
        .bvcc-cal-empty { padding: .5rem .625rem; opacity: .6; }
        .bvcc-cal-year { display: grid; grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr)); gap: 1rem; }
        .bvcc-cal-year section, .bvcc-cal-agenda { border: 1px solid var(--line); border-radius: .75rem; overflow: hidden; }
        .bvcc-cal-year h3 { display: flex; justify-content: space-between; align-items: baseline; padding: .5rem .75rem; background: var(--soft); font-weight: 600; font-size: .9375rem; }
        .bvcc-cal-year h3 button:hover { text-decoration: underline; }
        .bvcc-cal-year h3 small { font-weight: 400; opacity: .7; }
        .bvcc-cal-key { display: flex; flex-wrap: wrap; gap: .25rem 1rem; margin-top: 1rem; }
        .bvcc-cal-key span::before { content: ''; display: inline-block; width: .75rem; height: .75rem; border-radius: 2px; background: var(--c); margin-right: .375rem; vertical-align: -1px; }
        @media (max-width: 640px) {
            .bvcc-cal-grid { display: none; }
            .bvcc-cal-agenda { display: block; }
        }
    </style>
</x-filament-panels::page>

<?php

namespace App\Http\Controllers\Site;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Faq;
use App\Support\Markdown;
use App\Support\Present;
use App\Support\Schema;
use App\Support\Seo;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Response;

class EventController extends SiteController
{
    public function index(): Response
    {
        $events = Event::published()->upcoming()
            ->where('starts_at', '<', today()->addMonths(12))
            ->with(['venue', 'category', 'discipline'])->withCount('attendees')
            ->get();

        $faqs = Faq::published()->where('category', 'booking')->get();

        return $this->page('events/Index', [
            'events' => $events->map(Present::eventCard(...)),
            'categories' => EventCategory::orderBy('sort')
                ->whereIn('id', $events->pluck('event_category_id')->unique())
                ->get(['name', 'slug', 'colour']),
            'faqs' => Present::faqs($faqs),
        ], new Seo(
            title: 'Events and trips: sessions, river trips and courses',
            description: 'The '.config('site.short_name').' calendar: weekly paddling sessions, whitewater and touring trips, courses, canoe polo and socials. See dates, costs and how to book.',
            breadcrumbs: [['Events and trips', '/events']],
            schema: array_filter([
                Schema::faqPage($faqs),
                ...$events->where('status', EventStatus::Published)->take(10)->map(Schema::event(...))->values()->all(),
            ]),
        ));
    }

    public function show(string $slug): Response
    {
        $event = Event::published()->where('slug', $slug)
            ->with(['venue', 'category', 'discipline'])->withCount('attendees')
            ->firstOrFail();

        $related = Event::published()->upcoming()->whereNull('cancelled_at')
            ->whereKeyNot($event->id)
            ->where('event_category_id', $event->event_category_id)
            ->with(['venue', 'category', 'discipline'])->withCount('attendees')
            ->limit(3)->get();

        $subject = rawurlencode("Booking: {$event->title}, ".$event->starts_at->format('j M Y'));
        $body = rawurlencode("Hello,\n\nI would like to book onto {$event->title} on ".$event->starts_at->format('l j F Y').".\n\nNames of everyone coming:\n\nAre they members? \n\nKit needed:\n\nThanks");

        return $this->page('events/Show', [
            'event' => [
                ...Present::eventCard($event),
                'html' => Markdown::html($event->getAttribute('description')),
                'prices' => Present::eventPrices($event),
                'extra_costs' => $event->getAttribute('extra_costs'),
                'cancel_reason' => $event->getAttribute('cancel_reason'),
                'booking_open' => $event->bookingIsOpen(),
                'booking_closes' => $event->booking_closes_at?->format('l j F, g:ia'),
                'is_past' => $event->isPast(),
                'book_url' => 'mailto:'.config('site.bookings_email')."?subject={$subject}&body={$body}",
                'ical_url' => "/events/{$event->slug}.ics",
                'venue_detail' => $event->venue ? Present::venueCard($event->venue) : null,
            ],
            'related' => $related->map(Present::eventCard(...)),
        ], new Seo(
            title: $event->title.', '.$event->starts_at->format('j F Y'),
            description: $event->getAttribute('summary') ?: Markdown::text($event->getAttribute('description')),
            breadcrumbs: [['Events and trips', '/events'], [$event->title, "/events/{$event->slug}"]],
            schema: [Schema::event($event)],
            image: Present::image($event->getAttribute('hero_image'))['src'] ?? null,
            type: 'event',
            // Old events stay reachable for anyone with the link, but drop out of search.
            index: ! $event->starts_at->lt(today()->subMonths(3)),
        ), markdownUrl: "/events/{$event->slug}.md");
    }

    /**
     * One event, or every upcoming event, as an iCalendar feed people can
     * subscribe to from their phone.
     */
    public function ical(?string $slug = null): HttpResponse
    {
        $events = $slug === null
            ? Event::published()->where('starts_at', '>=', today()->subMonth())->with('venue')->orderBy('starts_at')->get()
            : Event::published()->where('slug', $slug)->with('venue')->get();

        abort_if($events->isEmpty() && $slug !== null, 404);

        $escape = fn (?string $text): string => addcslashes(str_replace(["\r\n", "\n"], '\n', (string) $text), ',;');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.config('site.short_name').'//Events//EN',
            'CALSCALE:GREGORIAN',
            'X-WR-CALNAME:'.$escape(config('site.name')),
        ];

        foreach ($events as $event) {
            $times = $event->all_day
                ? [
                    'DTSTART;VALUE=DATE:'.$event->starts_at->format('Ymd'),
                    'DTEND;VALUE=DATE:'.($event->ends_at ?? $event->starts_at)->addDay()->format('Ymd'),
                ]
                : [
                    'DTSTART:'.$event->starts_at->utc()->format('Ymd\THis\Z'),
                    'DTEND:'.($event->ends_at ?? $event->starts_at->addHours(2))->utc()->format('Ymd\THis\Z'),
                ];

            $lines = [
                ...$lines,
                'BEGIN:VEVENT',
                "UID:event-{$event->id}@".parse_url(config('app.url'), PHP_URL_HOST),
                'DTSTAMP:'.$event->getAttribute('updated_at')->utc()->format('Ymd\THis\Z'),
                ...$times,
                'SUMMARY:'.$escape(($event->isCancelled() ? 'CANCELLED: ' : '').$event->title),
                'DESCRIPTION:'.$escape($event->getAttribute('summary')),
                'URL:'.url("/events/{$event->slug}"),
                'STATUS:'.($event->isCancelled() ? 'CANCELLED' : 'CONFIRMED'),
                ...($event->venue ? ['LOCATION:'.$escape($event->venue->name.', '.$event->venue->fullAddress())] : []),
                'END:VEVENT',
            ];
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="'.($slug ?? 'bvcc-events').'.ics"',
        ]);
    }
}

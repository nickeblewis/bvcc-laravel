<?php

namespace App\Http\Controllers\Site;

use App\Models\Event;
use App\Models\Venue;
use App\Support\Markdown;
use App\Support\Present;
use App\Support\Schema;
use App\Support\Seo;
use Inertia\Response;

class VenueController extends SiteController
{
    public function index(): Response
    {
        $venues = Venue::where('is_listed', true)->orderBy('sort')->get();

        return $this->page('venues/Index', [
            'venues' => $venues->map(Present::venueCard(...)),
        ], new Seo(
            title: 'Where we paddle: venues in Aldershot and nearby',
            description: 'Where '.config('site.short_name').' paddles: Aldershot Lido in summer, a warm indoor pool in winter, the Basingstoke Canal in spring and autumn, and Hawley Lake for canoe polo.',
            breadcrumbs: [['Paddle with us', '/paddle'], ['Where we paddle', '/venues']],
            schema: $venues->map(Schema::place(...))->all(),
        ));
    }

    public function show(string $slug): Response
    {
        $venue = Venue::where('slug', $slug)->firstOrFail();

        $events = Event::published()->upcoming()->whereNull('cancelled_at')
            ->where('venue_id', $venue->id)
            ->with(['venue', 'category', 'discipline'])->withCount('attendees')
            ->limit(4)->get();

        return $this->page('venues/Show', [
            'venue' => [
                ...Present::venueCard($venue),
                'html' => Markdown::html($venue->getAttribute('description')),
                'access_notes' => $venue->getAttribute('access_notes'),
            ],
            'events' => $events->map(Present::eventCard(...)),
        ], new Seo(
            title: "{$venue->name}: paddling venue",
            description: trim($venue->getAttribute('summary').' '.Markdown::text($venue->getAttribute('description'))),
            breadcrumbs: [['Paddle with us', '/paddle'], ['Where we paddle', '/venues'], [$venue->name, "/venues/{$venue->slug}"]],
            schema: [Schema::place($venue)],
            image: Present::image($venue->getAttribute('hero_image'))['src'] ?? null,
        ));
    }
}

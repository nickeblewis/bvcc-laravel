<?php

namespace App\Http\Controllers\Site;

use App\Enums\PageSection;
use App\Models\Discipline;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Page;
use App\Models\Venue;
use App\Support\Markdown;
use App\Support\Present;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Response;

class PaddleController extends SiteController
{
    public function index(): Response
    {
        $nextSession = Event::published()->upcoming()->whereNull('cancelled_at')
            ->whereHas('category', fn (Builder $c) => $c->where('slug', EventCategory::SESSIONS))
            ->with(['venue', 'category', 'discipline'])->withCount('attendees')->first();

        return $this->page('paddle/Index', [
            'nextSession' => $nextSession ? Present::eventCard($nextSession) : null,
            'pages' => Page::published()->where('section', PageSection::Paddle)->get()
                ->map(fn (Page $p): array => ['title' => $p->title, 'summary' => $p->summary, 'url' => $p->path()]),
            'disciplines' => Discipline::published()->get()->map(Present::disciplineCard(...)),
            'venues' => Venue::where('is_listed', true)->orderBy('sort')->get()->map(Present::venueCard(...)),
        ], new Seo(
            title: 'Paddle with us: sessions, disciplines and coaching',
            description: 'Weekly kayak and canoe sessions every Wednesday in and around Aldershot, plus whitewater, canoe polo, touring, freestyle, sea kayaking and paddleboarding. Beginners welcome from age '.config('site.minimum_age').'.',
            breadcrumbs: [['Paddle with us', '/paddle']],
        ));
    }

    /**
     * A discipline, or an editable page in the Paddle section.
     */
    public function show(string $slug): Response
    {
        $discipline = Discipline::where('is_published', true)->where('slug', $slug)->first();

        if ($discipline === null) {
            return app(PageController::class)->show(PageSection::Paddle, $slug);
        }

        $events = Event::published()->upcoming()->whereNull('cancelled_at')
            ->where('discipline_id', $discipline->id)
            ->with(['venue', 'category', 'discipline'])->withCount('attendees')
            ->limit(3)->get();

        return $this->page('paddle/Show', [
            'discipline' => [
                ...Present::disciplineCard($discipline),
                'facts' => $discipline->facts ?? [],
                'html' => Markdown::html($discipline->getAttribute('body')),
                'video_url' => $discipline->getAttribute('video_url'),
                'contact_email' => $discipline->getAttribute('contact_email'),
            ],
            'events' => $events->map(Present::eventCard(...)),
            'others' => Discipline::published()->whereKeyNot($discipline->id)->get()->map(Present::disciplineCard(...)),
        ], new Seo(
            title: "{$discipline->name} in ".config('site.region'),
            description: $discipline->getAttribute('meta_description') ?: (string) $discipline->getAttribute('summary'),
            breadcrumbs: [['Paddle with us', '/paddle'], [$discipline->name, "/paddle/{$discipline->slug}"]],
            image: Present::image($discipline->getAttribute('hero_image'))['src'] ?? null,
        ), markdownUrl: "/paddle/{$discipline->slug}.md");
    }
}

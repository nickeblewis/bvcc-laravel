<?php

namespace App\Http\Controllers\Site;

use App\Models\Article;
use App\Models\Discipline;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Faq;
use App\Models\Venue;
use App\Support\Present;
use App\Support\Schema;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

class HomeController extends SiteController
{
    public function __invoke(): Response
    {
        $upcoming = Event::published()->upcoming()->whereNull('cancelled_at')
            ->with(['venue', 'category', 'discipline'])->withCount('attendees');

        $nextSession = (clone $upcoming)
            ->whereHas('category', fn (Builder $c) => $c->where('slug', EventCategory::SESSIONS))
            ->first();

        $events = (clone $upcoming)
            ->whereHas('category', fn (Builder $c) => $c->where('slug', '!=', EventCategory::SESSIONS))
            ->limit(3)->get();

        $faqs = Faq::published()->where('category', 'getting-started')->limit(4)->get();

        return $this->page('Home', [
            'nextSession' => $nextSession ? Present::eventCard($nextSession) : null,
            'events' => $events->map(Present::eventCard(...)),
            'disciplines' => Discipline::published()->get()->map(Present::disciplineCard(...)),
            'articles' => Article::published()->with('category')->limit(3)->get()->map(Present::articleCard(...)),
            'venues' => Venue::where('is_listed', true)->orderBy('sort')->limit(4)->get()->map(Present::venueCard(...)),
            'faqs' => Present::faqs($faqs),
            'heroImage' => Present::image(
                Storage::disk('public')->exists('brand/home-hero.jpg') ? 'brand/home-hero.jpg' : null,
                'Club members paddling kayaks at Aldershot Lido',
            ),
        ], new Seo(
            title: config('site.name').': kayaking and canoeing in '.config('site.locality').', '.config('site.region'),
            description: config('site.description'),
            schema: array_filter([Schema::faqPage($faqs)]),
            isHome: true,
        ));
    }
}

<?php

namespace App\Http\Controllers\Site;

use App\Enums\EventStatus;
use App\Enums\PageSection;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Discipline;
use App\Models\Event;
use App\Models\Faq;
use App\Models\MembershipTier;
use App\Models\Page;
use App\Models\Venue;
use App\Support\MarkdownExport;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * The files that help search engines and AI assistants find their way
 * around: robots.txt, sitemap.xml, llms.txt and llms-full.txt. All are
 * built from the database, so they never go stale.
 */
class DiscoveryController extends Controller
{
    public function robots(): Response
    {
        $lines = app()->environment('production')
            ? [
                '# Search engines and AI assistants are welcome.',
                'User-agent: *',
                'Allow: /',
                'Disallow: /admin',
                'Disallow: /img/',
                '',
                'Sitemap: '.url('/sitemap.xml'),
                '',
                '# A plain-text guide to this site for AI assistants: '.url('/llms.txt'),
            ]
            // Staging and local copies stay out of search results.
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function sitemap(): Response
    {
        return response()
            ->view('feeds.sitemap', ['urls' => $this->urls()])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Every page worth indexing. Events are limited to upcoming ones and
     * those from the last month, so old events do not crowd out the rest.
     *
     * @return Collection<int, array{loc: string, lastmod: string|null}>
     */
    private function urls(): Collection
    {
        $static = collect(['/', '/paddle', '/events', '/venues', '/join', '/join/hire', '/news', '/club', '/club/documents', '/contact', '/faqs'])
            ->map(fn (string $path): array => ['loc' => url($path), 'lastmod' => null]);

        $row = fn (string $path, Model $model): array => [
            'loc' => url($path),
            'lastmod' => ($updated = $model->getAttribute('updated_at')) instanceof CarbonInterface ? $updated->toDateString() : null,
        ];

        return $static
            ->concat(Discipline::published()->get()->map(fn (Discipline $d) => $row("/paddle/{$d->slug}", $d)))
            ->concat(Page::published()->where('slug', '!=', 'hire')->get()->map(fn (Page $p) => $row($p->path(), $p)))
            ->concat(Venue::where('is_listed', true)->get()->map(fn (Venue $v) => $row("/venues/{$v->slug}", $v)))
            ->concat(Event::published()->where('starts_at', '>=', today()->subMonth())->orderBy('starts_at')->get()
                ->map(fn (Event $e) => $row("/events/{$e->slug}", $e)))
            ->concat(Article::published()->get()->map(fn (Article $a) => $row("/news/{$a->slug}", $a)))
            ->values();
    }

    /**
     * llms.txt: a short, linked summary of the club and the site, following
     * the llmstxt.org layout.
     */
    public function llms(): Response
    {
        $link = fn (string $title, string $path, ?string $note = null): string => "- [{$title}](".url($path).')'.(filled($note) ? ": {$note}" : '');

        $events = Event::published()->upcoming()->where('status', EventStatus::Published)->with('venue')->limit(12)->get();

        $sections = [
            '# '.config('site.name'),
            '> '.config('site.description'),
            implode("\n", [
                '- **Founded:** '.config('site.founded'),
                '- **Based at:** Aldershot Lido, Guildford Road, Aldershot, Hampshire GU12 4BP',
                '- **Meets:** every Wednesday evening; the venue changes with the season',
                '- **Who can come:** all levels, age '.config('site.minimum_age').' and over; beginners welcome and kit is provided',
                '- **Affiliated to:** '.config('site.governing_body.name'),
                '- **Contact:** '.config('site.email'),
            ]),
            "## Start here\n\n".implode("\n", [
                $link('Beginners and coaching', '/paddle/beginners.md', 'how to try paddling with the club'),
                $link('Weekly sessions', '/paddle/weekly-sessions.md', 'when and where the club meets'),
                $link('Join: membership and fees', '/join', 'tiers, prices and session fees'),
                $link('Frequently asked questions', '/faqs', 'short answers to common questions'),
                $link('Contact', '/contact'),
            ]),
            "## Membership\n\n".MembershipTier::where('on_sale', true)->orderBy('sort')->get()
                ->map(fn (MembershipTier $t): string => "- **{$t->name}:** ".($t->initial_pence ? Money::format($t->initial_pence).($t->monthly_pence ? ' to join' : ' a year') : 'free')
                    .($t->monthly_pence ? ', then '.Money::format($t->monthly_pence).' a month' : ''))
                ->implode("\n"),
            "## Disciplines\n\n".Discipline::published()->get()
                ->map(fn (Discipline $d): string => $link($d->name, "/paddle/{$d->slug}.md", $d->getAttribute('summary')))->implode("\n"),
            "## Where we paddle\n\n".Venue::where('is_listed', true)->orderBy('sort')->get()
                ->map(fn (Venue $v): string => $link($v->name, "/venues/{$v->slug}", $v->getAttribute('summary')))->implode("\n"),
            "## Upcoming events\n\n".($events->isEmpty()
                ? 'See the [events calendar]('.url('/events').').'
                : $events->map(fn (Event $e): string => $link($e->title, "/events/{$e->slug}.md", $e->starts_at->format('D j M Y').($e->venue ? ", {$e->venue->name}" : '')))->implode("\n")),
            "## Club\n\n".Page::published()->where('section', PageSection::Club)->get()
                ->map(fn (Page $p): string => $link($p->title, $p->path().'.md', $p->summary))->implode("\n"),
            "## Optional\n\n".implode("\n", [
                $link('Everything in one file', '/llms-full.txt', 'the full text of the main pages'),
                $link('Events calendar feed', '/events/calendar.ics', 'iCalendar'),
                $link('News feed', '/news/feed.xml', 'RSS'),
                $link('Sitemap', '/sitemap.xml'),
            ]),
        ];

        return $this->text(implode("\n\n", $sections)."\n");
    }

    /**
     * llms-full.txt: the content itself, in one Markdown file.
     */
    public function llmsFull(): Response
    {
        $faqs = Faq::published()->get()->map(fn (Faq $f): string => "### {$f->question}\n\n{$f->answer}")->implode("\n\n");

        $documents = collect(['# '.config('site.name')."\n\n> ".config('site.description')])
            ->concat(Page::published()->whereIn('section', [PageSection::Paddle, PageSection::Join])->whereNotNull('body')->get()->map(MarkdownExport::page(...)))
            ->concat(Discipline::published()->get()->map(MarkdownExport::discipline(...)))
            ->push("# Frequently asked questions\n\n{$faqs}\n")
            ->concat(Event::published()->upcoming()->where('status', EventStatus::Published)->with(['venue', 'category', 'discipline'])->limit(20)->get()->map(MarkdownExport::event(...)));

        return $this->text($documents->implode("\n---\n\n"));
    }

    private function text(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}

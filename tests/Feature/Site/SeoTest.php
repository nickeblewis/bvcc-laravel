<?php

use App\Console\Commands\ImportWordPress;
use App\Enums\EventStatus;
use App\Models\Article;
use App\Models\Event;
use App\Models\Redirect;
use Database\Seeders\ClubSeeder;
use League\HTMLToMarkdown\HtmlConverter;

// F-04 (SEO plumbing), F-21 (redirects), F-22 (sitemap, robots, llms.txt), F-23 (import). See docs/seo.md.

beforeEach(fn () => $this->seed(ClubSeeder::class));

/**
 * The JSON-LD graph printed in a page's HTML.
 *
 * @return list<array<string, mixed>>
 */
function jsonLd(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);

    return json_decode($match[1] ?? 'null', true, flags: JSON_THROW_ON_ERROR)['@graph'];
}

it('prints a title, description, canonical link and social tags in the HTML of every page', function (string $path) {
    $html = $this->get($path)->assertOk()->getContent();

    expect($html)
        ->toMatch('#<title>[^<]{20,}</title>#')
        ->toMatch('#<meta name="description" content="[^"]{50,}">#')
        ->toContain('<link rel="canonical" href="'.url($path === '/' ? '/' : $path).'">')
        ->toContain('<meta property="og:title"')
        ->toContain('<meta property="og:image"')
        ->toContain('<meta name="twitter:card" content="summary_large_image">');

    expect(substr_count($html, '<title>'))->toBe(1);
})->with(['/', '/paddle', '/paddle/whitewater', '/events', '/events/barle-whitewater-day-trip-2026-10-24', '/join', '/venues/aldershot-lido', '/faqs', '/contact', '/club']);

it('describes the club as a SportsClub on every page', function () {
    $club = collect(jsonLd($this->get('/contact')->getContent()))->firstWhere('@id', url('/').'#club');

    expect($club['@type'])->toContain('SportsClub')
        ->and($club['name'])->toBe('Blackwater Valley Canoe Club')
        ->and($club['foundingDate'])->toBe('1986')
        ->and($club['address']['postalCode'])->toBe('GU12 4BP')
        ->and($club['sameAs'])->toContain('https://www.facebook.com/BlackwaterValleyCC');
});

it('describes an event with its date, place, status and a price per band', function () {
    $event = collect(jsonLd($this->get('/events/barle-whitewater-day-trip-2026-10-24')->getContent()))->firstWhere('@type', 'SportsEvent');

    expect($event['name'])->toBe('River Barle whitewater day trip')
        ->and($event['startDate'])->toStartWith('2026-10-24T08:00:00')
        ->and($event['eventStatus'])->toBe('https://schema.org/EventScheduled')
        ->and(collect($event['offers'])->pluck('price', 'name')->all())->toBe([
            'Non-member' => '15.00', 'Pay as you paddle' => '10.00', 'Monthly subscriber' => '0.00', 'Affiliate' => '10.00',
        ])
        ->and($event['offers'][0]['priceCurrency'])->toBe('GBP');
});

it('marks a cancelled event as cancelled in its structured data', function () {
    $event = Event::factory()->create(['status' => EventStatus::Cancelled, 'cancelled_at' => now()]);

    $node = collect(jsonLd($this->get("/events/{$event->slug}")->getContent()))->firstWhere('@type', 'SportsEvent');

    expect($node['eventStatus'])->toBe('https://schema.org/EventCancelled');
});

it('adds FAQ, breadcrumb, place and article structured data where it applies', function () {
    Article::create(['title' => 'Canal sessions are back', 'slug' => 'canal', 'published_at' => now(), 'body' => 'We are back on the canal.']);

    $types = fn (string $path) => collect(jsonLd($this->get($path)->getContent()))->pluck('@type')->flatten()->all();

    expect($types('/faqs'))->toContain('FAQPage', 'BreadcrumbList')
        ->and($types('/join'))->toContain('FAQPage')
        ->and($types('/venues/aldershot-lido'))->toContain('Place')
        ->and($types('/news/canal'))->toContain('NewsArticle')
        ->and($types('/'))->not->toContain('BreadcrumbList');
});

it('keeps test and staging copies out of search engines', function () {
    expect($this->get('/')->getContent())->toContain('<meta name="robots" content="noindex, nofollow">');
    $this->get('/robots.txt')->assertSee('Disallow: /');
});

it('invites search engines and AI crawlers in on the live site', function () {
    app()->detectEnvironment(fn () => 'production');

    expect($this->get('/')->getContent())->toContain('<meta name="robots" content="index, follow');

    $this->get('/robots.txt')
        ->assertSee('Allow: /')
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.url('/sitemap.xml'));
});

it('tells search engines not to index filtered news lists or long-past events', function () {
    app()->detectEnvironment(fn () => 'production');
    $old = Event::factory()->create(['starts_at' => now()->subMonths(6), 'ends_at' => now()->subMonths(6)]);

    expect($this->get("/events/{$old->slug}")->getContent())->toContain('content="noindex, nofollow"')
        ->and($this->get('/news?category=club-trips')->getContent())->toContain('content="noindex, nofollow"');
});

it('lists pages, disciplines, venues, news and upcoming events in the sitemap, but not old events, drafts or the admin', function () {
    $old = Event::factory()->create(['starts_at' => now()->subMonths(4)]);
    $draft = Event::factory()->create(['status' => EventStatus::Draft]);
    Article::create(['title' => 'News', 'slug' => 'some-news', 'published_at' => now()]);

    $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8')->getContent();

    expect(simplexml_load_string($xml))->not->toBeFalse();

    expect($xml)
        ->toContain('<loc>'.url('/').'</loc>')
        ->toContain(url('/paddle/whitewater'))
        ->toContain(url('/paddle/weekly-sessions'))
        ->toContain(url('/venues/aldershot-lido'))
        ->toContain(url('/events/barle-whitewater-day-trip-2026-10-24'))
        ->toContain(url('/news/some-news'))
        ->not->toContain($old->slug)
        ->not->toContain($draft->slug)
        ->not->toContain('/admin')
        ->not->toContain('/login');
});

it('serves llms.txt with the club facts and links to Markdown pages', function () {
    $this->get('/llms.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertSee('# Blackwater Valley Canoe Club', false)
        ->assertSee('**Founded:** 1986', false)
        ->assertSee(url('/paddle/whitewater.md'))
        ->assertSee('**Adult: pay as you paddle:** £35 a year', false)
        ->assertSee('River Barle whitewater day trip');
});

it('serves the main content in one file as llms-full.txt', function () {
    $this->get('/llms-full.txt')
        ->assertOk()
        ->assertSee('# Weekly sessions', false)
        ->assertSee('# Whitewater', false)
        ->assertSee('### How much does a session cost?', false);
});

it('serves a Markdown twin of content pages, pointing back at the HTML page', function (string $path, string $heading) {
    $this->get("{$path}.md")
        ->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertHeader('Link', '<'.url($path).'>; rel="canonical"')
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee("# {$heading}", false);

    expect($this->get($path)->getContent())->toContain('<link rel="alternate" type="text/markdown" href="'.url("{$path}.md").'">');
})->with([
    ['/paddle/whitewater', 'Whitewater'],
    ['/paddle/weekly-sessions', 'Weekly sessions'],
    ['/events/barle-whitewater-day-trip-2026-10-24', 'River Barle whitewater day trip'],
]);

it('permanently redirects old WordPress addresses to their new homes', function (string $old, string $new) {
    $this->get($old)->assertStatus(301)->assertRedirect($new);
})->with([
    ['/about-us/whitewater/', '/paddle/whitewater'],
    ['/about-us/weekly-sessions', '/paddle/weekly-sessions'],
    ['/membership/club-fees/', '/join'],
    ['/documents/privacy-policy/', '/club/privacy-policy'],
    ['/Contact-Us/', '/contact'],
    ['/events/search-events/dartmoor-weekend-trip-2026/', '/events'],
    ['/events/locations/hawley-lake/', '/venues'],
    ['/news/category/club-trips/', '/news?category=club-trips'],
    ['/news/2026/03/02/a-post-that-was-not-imported/', '/news'],
]);

it('redirects an old dated news address to the imported post and counts redirect use', function () {
    Article::create(['title' => 'New trips', 'slug' => 'new-trips-announced-for-2026', 'published_at' => now()]);

    $this->get('/news/2026/03/02/new-trips-announced-for-2026/')->assertStatus(301)->assertRedirect('/news/new-trips-announced-for-2026');

    $this->get('/hire/');
    expect(Redirect::where('from_path', '/hire')->value('hits'))->toBe(1);
});

it('cleans WordPress HTML into Markdown on import', function () {
    $markdown = app(ImportWordPress::class)->toMarkdown(<<<'HTML'
        <header><h1 class="entry-title">Hire</h1></header>
        <p>See the <a href="https://bvcc.org.uk/hire/hire-terms/">hire terms</a> and <a href="https://development.bvcc.org.uk/membership/">membership</a>.</p>
        <script>alert(1)</script>[pmpro_levels]
        <figure class="wp-block-gallery"><img src="a.jpg"></figure>
        <p>Pay by transfer to sort code 12-34-56 account 12345678.</p>
        <h2>Storage</h2><ul><li>Lido</li></ul>
        HTML);

    expect($markdown)
        ->toContain('[hire terms](/join/hire-terms)')
        ->toContain('[membership](/join)')
        ->toContain('## Storage')
        ->toContain('- Lido')
        ->not->toContain('# Hire')
        ->not->toContain('alert')
        ->not->toContain('pmpro')
        ->not->toContain('12-34-56');
})->skip(fn () => ! class_exists(HtmlConverter::class));

<?php

use App\Enums\EventStatus;
use App\Models\Article;
use App\Models\Event;
use App\Models\Member;
use App\Services\Bookings;
use Database\Seeders\ClubSeeder;
use Inertia\Testing\AssertableInertia as Assert;

// F-10 to F-20: the public pages (docs/spec.md).

beforeEach(fn () => $this->seed(ClubSeeder::class));

it('serves every public page', function (string $path, string $component) {
    $this->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page->component("site/{$component}"));
})->with([
    ['/', 'Home'],
    ['/paddle', 'paddle/Index'],
    ['/paddle/whitewater', 'paddle/Show'],
    ['/paddle/weekly-sessions', 'ContentPage'],
    ['/paddle/beginners', 'ContentPage'],
    ['/events', 'events/Index'],
    ['/events/barle-whitewater-day-trip-2026-10-24', 'events/Show'],
    ['/venues', 'venues/Index'],
    ['/venues/aldershot-lido', 'venues/Show'],
    ['/join', 'join/Index'],
    ['/join/hire', 'join/Hire'],
    ['/join/membership-terms', 'ContentPage'],
    ['/news', 'news/Index'],
    ['/club', 'club/Index'],
    ['/club/documents', 'club/Documents'],
    ['/club/constitution', 'ContentPage'],
    ['/contact', 'Contact'],
    ['/faqs', 'Faqs'],
]);

it('returns 404 for a page that does not exist', function (string $path) {
    $this->get($path)->assertNotFound();
})->with(['/paddle/unicycling', '/events/no-such-event', '/club/secrets', '/news/nothing', '/nope']);

it('shows the next weekly session and upcoming trips on the home page', function () {
    $session = Event::factory()->create(['title' => 'Pool session', 'starts_at' => now()->addDays(2)]);

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('nextSession.title', 'Pool session')
        ->where('nextSession.url', "/events/{$session->slug}")
        ->has('events', 2)
        ->has('disciplines', 6)
        ->has('faqs'));
});

it('builds the fee table from the same rates the booking system charges', function () {
    $this->get('/join')->assertInertia(fn (Assert $page) => $page
        ->has('tiers', 8)
        ->where('feeTable.rows.0.band', 'Non-member')
        ->where('feeTable.rows.0.prices', ['£10', '£10', '£15', '£5'])
        ->where('feeTable.rows.1.prices', ['£6', '£6', '£10', 'n/a'])
        ->where('feeTable.rows.2.prices', ['Free', 'Free', 'Free', 'n/a']));
});

it('shows each band\'s price and how to book on an event page', function () {
    $this->get('/events/dartmoor-whitewater-weekend-2026-11-21')->assertInertia(fn (Assert $page) => $page
        ->where('event.prices.0', ['band' => 'Non-member', 'price' => '£30'])
        ->where('event.prices.2', ['band' => 'Monthly subscriber', 'price' => 'Free'])
        ->where('event.extra_costs', 'Accommodation is charged separately once confirmed.')
        ->where('event.grade', 3)
        ->where('event.book_url', fn (string $url) => str_starts_with($url, 'mailto:'.config('site.bookings_email'))));
});

it('hides draft events from the public', function () {
    $draft = Event::factory()->create(['status' => EventStatus::Draft, 'title' => 'Secret plan']);

    $this->get("/events/{$draft->slug}")->assertNotFound();
    $this->get('/events')->assertInertia(fn (Assert $page) => $page
        ->where('events', fn ($events) => collect($events)->doesntContain('title', 'Secret plan')));
});

it('keeps a cancelled event visible and marked as cancelled', function () {
    $event = Event::factory()->create();
    app(Bookings::class)->cancelEvent($event, 'River too low');

    $this->get("/events/{$event->slug}")->assertInertia(fn (Assert $page) => $page
        ->where('event.cancelled', true)
        ->where('event.cancel_reason', 'River too low')
        ->where('event.booking_open', false));
});

it('shows places left and full events without saying who is booked', function () {
    $event = Event::factory()->create(['capacity' => 1]);
    $member = Member::factory()->create(['first_name' => 'Zebedee', 'medical_notes' => 'Private']);
    app(Bookings::class)->create($event, ['member_id' => $member->id], [['member_id' => $member->id]]);

    $response = $this->get("/events/{$event->slug}");

    $response->assertInertia(fn (Assert $page) => $page->where('event.is_full', true)->where('event.places_left', 0));
    expect($response->getContent())->not->toContain('Zebedee')->not->toContain('Private');
});

it('lists only published news, newest first', function () {
    Article::create(['title' => 'Old', 'slug' => 'old', 'published_at' => now()->subYear()]);
    Article::create(['title' => 'New', 'slug' => 'new', 'published_at' => now()->subDay()]);
    Article::create(['title' => 'Draft', 'slug' => 'draft']);
    Article::create(['title' => 'Scheduled', 'slug' => 'scheduled', 'published_at' => now()->addWeek()]);

    $this->get('/news')->assertInertia(fn (Assert $page) => $page
        ->where('articles', fn ($articles) => collect($articles)->pluck('title')->all() === ['New', 'Old']));

    $this->get('/news/draft')->assertNotFound();
    $this->get('/news/scheduled')->assertNotFound();
});

it('escapes HTML typed into page content', function () {
    Article::create(['title' => 'Hello', 'slug' => 'hello', 'published_at' => now(), 'body' => "Hi <script>alert(1)</script>\n\n[x](javascript:alert(1))"]);

    $this->get('/news/hello')->assertInertia(fn (Assert $page) => $page
        ->where('article.html', fn (string $html) => ! str_contains($html, '<script>') && ! str_contains($html, 'javascript:')));
});

it('serves the events calendar and single events as iCalendar', function () {
    $this->get('/events/calendar.ics')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VCALENDAR')
        ->assertSee('SUMMARY:River Barle whitewater day trip')
        ->assertSee('DTSTART;VALUE=DATE:20261121');

    $this->get('/events/barle-whitewater-day-trip-2026-10-24.ics')
        ->assertOk()
        ->assertSee('DTSTART:20261024T070000Z')
        ->assertDontSee('Dartmoor');
});

it('serves a news feed', function () {
    Article::create(['title' => 'Pool sessions are back', 'slug' => 'pool', 'published_at' => now()]);

    $this->get('/news/feed.xml')->assertOk()->assertSee('<title>Pool sessions are back</title>', false);
});

it('does not offer public registration', function () {
    // The old site's /register address now leads to the Join page; there is no sign-up form.
    $this->get('/register')->assertRedirect('/join');
    $this->post('/register', ['email' => 'x@example.com'])->assertStatus(405);
});

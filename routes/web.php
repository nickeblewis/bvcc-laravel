<?php

use App\Enums\PageSection;
use App\Http\Controllers\Site\ClubController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\DiscoveryController;
use App\Http\Controllers\Site\EventController;
use App\Http\Controllers\Site\FaqController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\ImageController;
use App\Http\Controllers\Site\JoinController;
use App\Http\Controllers\Site\MarkdownController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\PaddleController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\RedirectController;
use App\Http\Controllers\Site\VenueController;
use Illuminate\Support\Facades\Route;

/*
 * The public site. Slugs never contain a dot, which lets "/x/slug.md" and
 * "/events/slug.ics" sit beside "/x/slug".
 */
Route::pattern('slug', '[a-z0-9-]+');

Route::get('/', HomeController::class)->name('home');

Route::get('/paddle', [PaddleController::class, 'index'])->name('paddle');
Route::get('/paddle/{slug}', [PaddleController::class, 'show']);

Route::get('/events', [EventController::class, 'index'])->name('events');
Route::get('/events/calendar.ics', [EventController::class, 'ical']);
Route::get('/events/{slug}.ics', [EventController::class, 'ical']);
Route::get('/events/{slug}.md', [MarkdownController::class, 'event']);
Route::get('/events/{slug}', [EventController::class, 'show']);

Route::get('/venues', [VenueController::class, 'index'])->name('venues');
Route::get('/venues/{slug}', [VenueController::class, 'show']);

Route::get('/join', [JoinController::class, 'index'])->name('join');
Route::get('/join/hire', [JoinController::class, 'hire'])->name('hire');
Route::post('/join/hire', [JoinController::class, 'storeHire'])->middleware('throttle:5,10');
Route::get('/join/{slug}', fn (string $slug) => app(PageController::class)->show(PageSection::Join, $slug));

Route::get('/news', [NewsController::class, 'index'])->name('news');
Route::get('/news/feed.xml', [NewsController::class, 'feed']);
Route::get('/news/{slug}.md', [MarkdownController::class, 'article']);
Route::get('/news/{slug}', [NewsController::class, 'show']);

Route::get('/club', [ClubController::class, 'index'])->name('club');
Route::get('/club/documents', [ClubController::class, 'documents']);
Route::get('/club/{slug}', fn (string $slug) => app(PageController::class)->show(PageSection::Club, $slug));

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,10');

Route::get('/faqs', FaqController::class)->name('faqs');

Route::get('/{section}/{slug}.md', [MarkdownController::class, 'page'])->whereIn('section', array_column(PageSection::cases(), 'value'));

Route::get('/robots.txt', [DiscoveryController::class, 'robots']);
Route::get('/sitemap.xml', [DiscoveryController::class, 'sitemap']);
Route::get('/llms.txt', [DiscoveryController::class, 'llms']);
Route::get('/llms-full.txt', [DiscoveryController::class, 'llmsFull']);

Route::get('/img/{width}/{path}', ImageController::class)->whereNumber('width')->where('path', '.+');

// The starter kit's account pages are kept for member logins in a later
// phase. Staff sign in at /admin.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => redirect('/admin'))->name('dashboard');
});

require __DIR__.'/settings.php';

// Must stay last: old WordPress addresses, then a 404.
Route::fallback(RedirectController::class);

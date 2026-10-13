<?php

namespace App\Support;

use App\Enums\EventStatus;
use App\Enums\FeeBand;
use App\Models\Article;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Venue;
use App\Services\FeeCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * schema.org nodes for the JSON-LD graph. Each returns a plain array; nodes
 * refer to each other by @id so the club is described once.
 */
class Schema
{
    public static function clubId(): string
    {
        return url('/').'#club';
    }

    /**
     * @return array<string, mixed>
     */
    public static function club(): array
    {
        $home = once(fn (): ?Venue => Venue::where('slug', config('site.home_venue'))->first());

        return array_filter([
            '@type' => ['SportsClub', 'SportsOrganization'],
            '@id' => static::clubId(),
            'name' => config('site.name'),
            'alternateName' => config('site.short_name'),
            'url' => url('/'),
            'logo' => url('/images/brand/icon-512.png'),
            'image' => url('/images/brand/social.png'),
            'description' => config('site.description'),
            'slogan' => config('site.tagline'),
            'foundingDate' => (string) config('site.founded'),
            'email' => config('site.email'),
            'sport' => ['Kayaking', 'Canoeing', 'Canoe polo', 'Whitewater kayaking', 'Stand-up paddleboarding'],
            'areaServed' => ['Aldershot', 'Farnborough', 'Farnham', 'Fleet', 'Camberley', 'Yateley', 'Hampshire', 'Surrey'],
            'memberOf' => [
                '@type' => 'SportsOrganization',
                'name' => config('site.governing_body.name'),
                'url' => config('site.governing_body.url'),
            ],
            'address' => $home ? self::address($home) : null,
            'geo' => $home ? self::geo($home) : null,
            'sameAs' => array_values(array_filter((array) config('site.social'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => config('site.name'),
            'inLanguage' => 'en-GB',
            'publisher' => ['@id' => static::clubId()],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $crumbs
     * @return array<string, mixed>|null
     */
    public static function breadcrumbs(array $crumbs): ?array
    {
        if ($crumbs === []) {
            return null;
        }

        $items = [['Home', '/'], ...$crumbs];

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                'item' => url($crumb[1]),
            ], $items, array_keys($items)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function event(Event $event): array
    {
        $prices = array_filter(app(FeeCalculator::class)->pricesFor($event), fn (?int $p): bool => $p !== null);
        $url = url("/events/{$event->slug}");

        return array_filter([
            '@type' => 'SportsEvent',
            '@id' => $url.'#event',
            'name' => $event->title,
            'description' => Markdown::text($event->getAttribute('summary') ?: $event->getAttribute('description'), 300),
            'url' => $url,
            'startDate' => $event->all_day ? $event->starts_at->toDateString() : $event->starts_at->toIso8601String(),
            'endDate' => $event->ends_at
                ? ($event->all_day ? $event->ends_at->toDateString() : $event->ends_at->toIso8601String())
                : null,
            'eventStatus' => 'https://schema.org/'.($event->status === EventStatus::Cancelled ? 'EventCancelled' : 'EventScheduled'),
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'sport' => $event->discipline->name ?? 'Kayaking',
            'image' => filled($event->getAttribute('hero_image')) ? url(Storage::disk('public')->url($event->getAttribute('hero_image'))) : null,
            'location' => $event->venue ? static::place($event->venue) : null,
            'organizer' => ['@id' => static::clubId()],
            'maximumAttendeeCapacity' => $event->capacity,
            'offers' => $prices === [] ? null : array_map(fn (int $pence, string $band): array => [
                '@type' => 'Offer',
                'name' => FeeBand::from($band)->getLabel(),
                'price' => number_format($pence / 100, 2, '.', ''),
                'priceCurrency' => 'GBP',
                'url' => $url,
                'availability' => 'https://schema.org/'.($event->bookingIsOpen() ? 'InStock' : 'SoldOut'),
            ], $prices, array_keys($prices)),
        ], fn ($value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function place(Venue $venue): array
    {
        return array_filter([
            '@type' => 'Place',
            '@id' => url("/venues/{$venue->slug}").'#place',
            'name' => $venue->name,
            'url' => url("/venues/{$venue->slug}"),
            'address' => self::address($venue),
            'geo' => self::geo($venue),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function article(Article $article): array
    {
        $url = url("/news/{$article->slug}");

        return array_filter([
            '@type' => 'NewsArticle',
            '@id' => $url.'#article',
            'headline' => $article->title,
            'description' => $article->excerpt ?: Markdown::text($article->body, 200),
            'url' => $url,
            'mainEntityOfPage' => $url,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->getAttribute('updated_at')?->toIso8601String(),
            'image' => filled($article->getAttribute('hero_image')) ? url(Storage::disk('public')->url($article->getAttribute('hero_image'))) : null,
            'author' => ['@id' => static::clubId()],
            'publisher' => ['@id' => static::clubId()],
        ]);
    }

    /**
     * @param  Collection<int, Faq>  $faqs
     * @return array<string, mixed>|null
     */
    public static function faqPage(Collection $faqs): ?array
    {
        if ($faqs->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn (Faq $faq): array => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => Markdown::text($faq->answer, 2000)],
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function address(Venue $venue): array
    {
        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $venue->address,
            'addressLocality' => $venue->town,
            'addressRegion' => config('site.region'),
            'postalCode' => $venue->postcode,
            'addressCountry' => config('site.country'),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function geo(Venue $venue): ?array
    {
        return $venue->latitude && $venue->longitude
            ? ['@type' => 'GeoCoordinates', 'latitude' => $venue->latitude, 'longitude' => $venue->longitude]
            : null;
    }
}

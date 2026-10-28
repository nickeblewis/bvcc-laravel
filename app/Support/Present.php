<?php

namespace App\Support;

use App\Enums\FeeBand;
use App\Models\Article;
use App\Models\Discipline;
use App\Models\Event;
use App\Models\Faq;
use App\Models\MembershipTier;
use App\Models\Page;
use App\Models\Venue;
use App\Services\FeeCalculator;
use Illuminate\Support\Collection;

/**
 * Turns models into the plain arrays the Vue pages receive. Only what the
 * public may see goes in here: no member, booking or payment data, and no
 * leader names. Dates are formatted on the server so the server-rendered
 * and browser-rendered HTML always match.
 */
class Present
{
    /** Widths the image route will produce; keep in step with ImageController. */
    public const IMAGE_WIDTHS = [480, 768, 1200, 1600];

    /**
     * A responsive image from the public disk.
     *
     * @return array{src: string, srcset: string, alt: string}|null
     */
    public static function image(?string $path, ?string $alt = null): ?array
    {
        if (blank($path)) {
            return null;
        }

        $url = fn (int $width): string => "/img/{$width}/".implode('/', array_map('rawurlencode', explode('/', $path)));

        return [
            'src' => $url(1200),
            'srcset' => implode(', ', array_map(fn (int $w): string => "{$url($w)} {$w}w", static::IMAGE_WIDTHS)),
            'alt' => $alt ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function eventCard(Event $event): array
    {
        $prices = array_filter(app(FeeCalculator::class)->pricesFor($event), fn (?int $p): bool => $p !== null);
        $left = $event->capacity === null ? null : max(0, $event->capacity - (int) ($event->getAttribute('attendees_count') ?? $event->placesTaken()));
        $multiDay = $event->ends_at !== null && ! $event->ends_at->isSameDay($event->starts_at);

        return [
            'title' => $event->title,
            'url' => "/events/{$event->slug}",
            'summary' => $event->getAttribute('summary'),
            'starts_at' => $event->starts_at->toIso8601String(),
            'day' => $event->starts_at->format('j'),
            'month' => $event->starts_at->format('M'),
            'month_key' => $event->starts_at->format('Y-m'),
            'month_label' => $event->starts_at->format('F Y'),
            'date_key' => $event->starts_at->format('Y-m-d'),
            'weekday' => $event->starts_at->format('D'),
            'date_label' => $multiDay
                ? $event->starts_at->format('D j M').' to '.$event->ends_at->format('D j M Y')
                : $event->starts_at->format('l j F Y'),
            'time_label' => $event->all_day
                ? ($multiDay ? 'Weekend or longer' : 'All day')
                : static::time($event->starts_at).($event->ends_at ? ' to '.static::time($event->ends_at) : ''),
            'venue' => $event->venue ? ['name' => $event->venue->name, 'url' => "/venues/{$event->venue->slug}"] : null,
            'category' => ['name' => $event->category->name, 'slug' => $event->category->slug, 'colour' => $event->category->colour],
            'discipline' => $event->discipline?->name,
            'grade' => $event->grade,
            'suitability' => $event->getAttribute('suitability'),
            'cancelled' => $event->isCancelled(),
            'price_label' => static::priceRange($prices),
            'places_left' => $left,
            'is_full' => $left === 0,
            'image' => static::image($event->getAttribute('hero_image'), $event->getAttribute('hero_alt')),
        ];
    }

    /**
     * "7pm" or "7:30pm".
     */
    public static function time(\DateTimeInterface $time): string
    {
        return $time->format($time->format('i') === '00' ? 'ga' : 'g:ia');
    }

    /**
     * @param  array<string, int>  $prices
     */
    public static function priceRange(array $prices): ?string
    {
        if ($prices === []) {
            return null;
        }

        [$min, $max] = [min($prices), max($prices)];

        return match (true) {
            $max === 0 => 'Free',
            $min === $max => Money::format($min),
            $min === 0 => 'Free to '.Money::format($max),
            default => Money::format($min).' to '.Money::format($max),
        };
    }

    /**
     * The price each fee band pays, for the table on an event page.
     *
     * @return list<array{band: string, price: string}>
     */
    public static function eventPrices(Event $event): array
    {
        $rows = [];

        foreach (app(FeeCalculator::class)->pricesFor($event) as $band => $pence) {
            if ($pence !== null) {
                $rows[] = ['band' => FeeBand::from($band)->getLabel(), 'price' => $pence === 0 ? 'Free' : Money::format($pence)];
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public static function disciplineCard(Discipline $discipline): array
    {
        return [
            'name' => $discipline->name,
            'slug' => $discipline->slug,
            'url' => "/paddle/{$discipline->slug}",
            'summary' => $discipline->getAttribute('summary'),
            'image' => static::image($discipline->getAttribute('hero_image'), $discipline->getAttribute('hero_alt')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function articleCard(Article $article): array
    {
        return [
            'title' => $article->title,
            'url' => "/news/{$article->slug}",
            'excerpt' => $article->excerpt ?: Markdown::text($article->body, 180),
            'date' => $article->published_at?->format('j F Y'),
            'datetime' => $article->published_at?->toDateString(),
            'category' => $article->category?->name,
            'image' => static::image($article->getAttribute('hero_image'), $article->getAttribute('hero_alt')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function venueCard(Venue $venue): array
    {
        return [
            'name' => $venue->name,
            'url' => "/venues/{$venue->slug}",
            'summary' => $venue->getAttribute('summary'),
            'address' => $venue->fullAddress(),
            'map_url' => $venue->mapUrl(),
            'latitude' => $venue->latitude,
            'longitude' => $venue->longitude,
            'image' => static::image($venue->getAttribute('hero_image'), $venue->getAttribute('hero_alt')),
        ];
    }

    /**
     * An editable content page, ready for the generic page template.
     *
     * @return array<string, mixed>
     */
    public static function page(Page $page): array
    {
        return [
            'title' => $page->title,
            'summary' => $page->summary,
            'facts' => $page->facts ?? [],
            'html' => Markdown::html($page->body),
            'reviewed' => $page->reviewed_on?->format('j F Y'),
            'image' => static::image($page->getAttribute('hero_image'), $page->getAttribute('hero_alt')),
            'markdown_url' => $page->path().'.md',
        ];
    }

    /**
     * @param  Collection<int, Faq>  $faqs
     * @return array<int, array{question: string, html: string}>
     */
    public static function faqs(Collection $faqs): array
    {
        return $faqs->map(fn (Faq $faq): array => [
            'question' => $faq->question,
            'html' => Markdown::html($faq->answer),
        ])->values()->all();
    }

    /**
     * The membership tiers on sale, for the Join page and the sign-up form.
     *
     * @return array<int, array{id: int, name: string, price: string, monthly: string|null, eligibility: string|null, description: string|null, band: string}>
     */
    public static function tiers(): array
    {
        return MembershipTier::where('on_sale', true)->orderBy('sort')->get()->map(fn (MembershipTier $tier): array => [
            'id' => $tier->id,
            'name' => $tier->name,
            'price' => $tier->initial_pence === 0 ? 'Free' : Money::format($tier->initial_pence),
            'monthly' => $tier->monthly_pence > 0 ? Money::format($tier->monthly_pence) : null,
            'eligibility' => $tier->getAttribute('eligibility'),
            'description' => $tier->getAttribute('description'),
            'band' => $tier->fee_band->getLabel(),
        ])->values()->all();
    }
}

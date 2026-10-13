<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Discipline;
use App\Models\Event;
use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Plain Markdown versions of public content, for AI assistants and anyone
 * else who would rather not parse HTML. Used by the ".md" page addresses
 * and by /llms-full.txt.
 */
class MarkdownExport
{
    public static function page(Page $page): string
    {
        return self::document($page->title, $page->summary, $page->facts, $page->body, url($page->path()));
    }

    public static function discipline(Discipline $discipline): string
    {
        return self::document(
            $discipline->name,
            $discipline->getAttribute('summary'),
            $discipline->facts,
            $discipline->getAttribute('body'),
            url("/paddle/{$discipline->slug}"),
        );
    }

    public static function article(Article $article): string
    {
        return self::document(
            $article->title,
            $article->excerpt,
            [['label' => 'Published', 'value' => (string) $article->published_at?->format('j F Y')]],
            $article->body,
            url("/news/{$article->slug}"),
        );
    }

    public static function event(Event $event): string
    {
        $card = Present::eventCard($event);

        $facts = array_filter([
            ['label' => 'When', 'value' => $card['date_label'].', '.$card['time_label']],
            $event->venue ? ['label' => 'Where', 'value' => $event->venue->name.', '.$event->venue->fullAddress()] : null,
            ['label' => 'Type', 'value' => $event->category->name.($event->discipline ? ', '.$event->discipline->name : '')],
            $event->grade ? ['label' => 'River grade', 'value' => "Up to grade {$event->grade}"] : null,
            $card['suitability'] ? ['label' => 'Suitable for', 'value' => $card['suitability']] : null,
            $card['price_label'] ? ['label' => 'Cost', 'value' => collect(Present::eventPrices($event))->map(fn (array $p): string => "{$p['band']} {$p['price']}")->implode('; ')] : null,
            $event->getAttribute('extra_costs') ? ['label' => 'Extra costs', 'value' => $event->getAttribute('extra_costs')] : null,
            ['label' => 'Status', 'value' => $event->isCancelled() ? 'Cancelled' : ($event->bookingIsOpen() ? 'Open for booking' : 'Booking closed')],
            ['label' => 'To book', 'value' => 'Email '.config('site.bookings_email')],
        ]);

        return self::document($event->title, $event->getAttribute('summary'), array_values($facts), $event->getAttribute('description'), url("/events/{$event->slug}"));
    }

    /**
     * @param  list<array{label: string, value: string}>|null  $facts
     */
    private static function document(string $title, ?string $summary, ?array $facts, ?string $body, string $url): string
    {
        $parts = ["# {$title}"];

        if (filled($summary)) {
            $parts[] = "> {$summary}";
        }

        if (filled($facts)) {
            $parts[] = (new Collection($facts))->map(fn (array $fact): string => "- **{$fact['label']}:** {$fact['value']}")->implode("\n");
        }

        if (filled($body)) {
            $parts[] = trim($body);
        }

        $parts[] = "Source: {$url}";

        return implode("\n\n", $parts)."\n";
    }
}

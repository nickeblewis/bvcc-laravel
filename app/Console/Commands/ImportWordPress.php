<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Discipline;
use App\Models\MediaItem;
use App\Models\Page;
use App\Models\Redirect;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Copies public content from the old WordPress site through its REST API.
 * Safe to run again: everything is matched on its old address, and pages an
 * editor has since written are left alone unless --force is given.
 */
#[Signature('bvcc:import-wp
    {--only= : Comma-separated: media, posts, pages. Everything if omitted}
    {--force : Overwrite page and post text that already exists}')]
#[Description('Import news posts, page text and photos from the old WordPress site')]
class ImportWordPress extends Command
{
    /** Old pages whose photos become the first photo of a discipline here: old slug => discipline slug. */
    private const DISCIPLINE_PAGES = [
        'whitewater' => 'whitewater',
        'canoe-polo' => 'canoe-polo',
        'touring' => 'touring',
        'freestyle' => 'freestyle',
        'sea-kayaking' => 'sea-kayaking',
        'stand-up-paddleboarding' => 'stand-up-paddleboarding',
    ];

    private string $base = '';

    public function handle(): int
    {
        $this->base = rtrim((string) config('site.legacy_url'), '/');
        $only = array_filter(explode(',', (string) $this->option('only')));
        $wants = fn (string $part): bool => $only === [] || in_array($part, $only, true);

        if ($wants('media')) {
            $this->importMedia();
        }

        if ($wants('posts')) {
            $this->importPosts();
        }

        if ($wants('pages')) {
            $this->importPages();
        }

        return self::SUCCESS;
    }

    private function http(): PendingRequest
    {
        return Http::timeout(60)->retry(2, 1000, throw: false)->withUserAgent('BVCC site migration');
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $type, array $query = []): array
    {
        $items = [];

        for ($page = 1; $page <= 20; $page++) {
            $response = $this->http()->get("{$this->base}/wp-json/wp/v2/{$type}", ['per_page' => 100, 'page' => $page, ...$query]);

            if ($response->failed()) {
                break;
            }

            $items = [...$items, ...array_values((array) $response->json())];

            if ($page >= (int) $response->header('X-WP-TotalPages')) {
                break;
            }
        }

        return $items;
    }

    private function importMedia(): void
    {
        $items = $this->fetchAll('media', ['media_type' => 'image', '_fields' => 'id,source_url,alt_text,caption,media_details']);
        $this->info(count($items).' photos found.');
        $new = 0;

        $this->withProgressBar($items, function (array $item) use (&$new): void {
            if ($this->storeImage($item['source_url'], $item['alt_text'] ?? null, strip_tags($item['caption']['rendered'] ?? ''), $item['media_details'] ?? []) === true) {
                $new++;
            }
        });

        $this->newLine();

        // The old theme's welcome photo, which was never in the media library.
        $hero = $this->http()->get("{$this->base}/wp-content/themes/blackwatervalley/images/page-home/home-left.jpg");

        if ($hero->successful() && ! Storage::disk('public')->exists('brand/home-hero.jpg')) {
            Storage::disk('public')->put('brand/home-hero.jpg', $hero->body());
        }

        // Most of the old site's photos are in page galleries, not the API.
        foreach (self::DISCIPLINE_PAGES as $oldSlug => $slug) {
            $html = (string) $this->http()->get("{$this->base}/wp-json/wp/v2/pages", ['slug' => $oldSlug, '_fields' => 'content'])->json('0.content.rendered');

            foreach (array_slice($this->imageUrls($html), 0, 4) as $i => $url) {
                if ($this->storeImage($url) === true) {
                    $new++;
                }

                if ($i === 0 && $photo = MediaItem::where('source_url', $url)->first()) {
                    Discipline::where('slug', $slug)->whereNull('hero_image')->update(['hero_image' => $photo->file]);
                }
            }
        }

        $this->info("{$new} new photos imported. Add descriptions and replace them over time in Website > Photos.");
    }

    /**
     * Download one image into the media library. Returns true when it is new,
     * false when it was already there, and null when it could not be fetched.
     *
     * @param  array<string, mixed>  $details
     */
    private function storeImage(string $url, ?string $alt = null, ?string $caption = null, array $details = []): ?bool
    {
        if (! str_starts_with($url, $this->base) || MediaItem::where('source_url', $url)->exists()) {
            return str_starts_with($url, $this->base) ? false : null;
        }

        $response = $this->http()->get($url);
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        if ($response->failed() || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return null;
        }

        $file = 'media/'.Str::slug(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME)).'.'.$extension;

        Storage::disk('public')->put($file, $response->body());

        MediaItem::create([
            'file' => $file,
            'alt' => filled($alt) ? $alt : null,
            'caption' => filled($caption) ? trim($caption) : null,
            'width' => $details['width'] ?? null,
            'height' => $details['height'] ?? null,
            'is_imported' => true,
            'source_url' => $url,
        ]);

        return true;
    }

    /**
     * Photo addresses in a page's HTML, largest version first seen.
     *
     * @return array<int, string>
     */
    private function imageUrls(string $html): array
    {
        preg_match_all('#'.preg_quote($this->base, '#').'/wp-content/uploads/[^"\'\s?,)]+\.(?:jpe?g|png)#i', $html, $matches);

        return collect($matches[0])
            // "photo-1024x576.jpg" is a resized copy of "photo.jpg"; keep one of each, preferring a large one.
            ->groupBy(fn (string $url): string => (string) preg_replace('#-\d+x\d+(?=\.\w+$)#', '', $url))
            ->map(fn ($copies): string => (string) ($copies->first(fn (string $url): bool => (bool) preg_match('#-(1024|1536|2048)x\d+\.#', $url)) ?? $copies->first()))
            ->values()
            ->all();
    }

    private function importPosts(): void
    {
        $categories = collect($this->fetchAll('categories', ['_fields' => 'id,name,slug']))->keyBy('id');
        $posts = $this->fetchAll('posts', ['_embed' => 'wp:featuredmedia']);
        $count = 0;

        foreach ($posts as $post) {
            $article = Article::firstOrNew(['source_url' => $post['link']]);

            if ($article->exists && ! $this->option('force')) {
                continue;
            }

            // "All" was a catch-all on the old site, not a real category.
            $category = null;

            foreach ((array) ($post['categories'] ?? []) as $id) {
                if (($candidate = $categories->get($id)) !== null && $candidate['slug'] !== 'all') {
                    $category = $candidate;

                    break;
                }
            }

            $image = $post['_embedded']['wp:featuredmedia'][0] ?? null;

            if ($image && isset($image['source_url'])) {
                $this->storeImage($image['source_url'], $image['alt_text'] ?? null);
            }

            $article->fill([
                'title' => $this->plain($post['title']['rendered']),
                'slug' => $article->slug ?? $post['slug'],
                'excerpt' => Str::limit($this->plain($post['excerpt']['rendered'] ?? ''), 300),
                'body' => $this->toMarkdown($post['content']['rendered']),
                'published_at' => Carbon::parse($post['date']),
                'article_category_id' => $category
                    ? ArticleCategory::firstOrCreate(['slug' => $category['slug']], ['name' => $this->plain($category['name'])])->id
                    : null,
                'hero_image' => $image ? MediaItem::where('source_url', $image['source_url'] ?? '')->value('file') : null,
                'hero_alt' => $image['alt_text'] ?? null,
            ])->save();

            $count++;
        }

        $this->info("{$count} news posts imported.");
    }

    /**
     * Fill in the text of pages that were seeded with a source address and
     * no body: the long policy and terms pages. They are flagged for review,
     * because the old text has out-of-date sections.
     */
    private function importPages(): void
    {
        $count = 0;

        foreach (Page::whereNotNull('source_url')->get() as $page) {
            if (filled($page->body) && ! $this->option('force')) {
                continue;
            }

            $slug = basename(rtrim((string) parse_url((string) $page->getAttribute('source_url'), PHP_URL_PATH), '/'));
            $source = $this->http()->get("{$this->base}/wp-json/wp/v2/pages", ['slug' => $slug, '_fields' => 'content,modified'])->json('0');

            if (! $source) {
                $this->warn("Not found on the old site: {$slug}");

                continue;
            }

            $page->update([
                'body' => $this->toMarkdown($source['content']['rendered']),
                'reviewed_on' => null,
                'to_confirm' => true,
            ]);

            $count++;
        }

        $this->info("{$count} pages imported and flagged for review in Website > Pages.");
    }

    private function plain(string $html): string
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));
    }

    /**
     * WordPress HTML to clean Markdown: no scripts, galleries, shortcodes or
     * page-title headings; old links pointed at their new homes.
     */
    public function toMarkdown(string $html): string
    {
        $this->base = rtrim((string) config('site.legacy_url'), '/');

        // Drop things that do not belong in body text.
        $html = (string) preg_replace('#<(script|style|iframe|form|figure|nav)\b.*?</\1>#is', '', $html);
        $html = (string) preg_replace('#<h1\b.*?</h1>#is', '', $html);
        $html = (string) preg_replace('#\[/?[a-z_]+[^\]]*\]#i', '', $html);

        $converter = new HtmlConverter(['strip_tags' => true, 'header_style' => 'atx', 'hard_break' => true, 'remove_nodes' => 'img svg button input select']);
        $converter->getEnvironment()->addConverter(new TableConverter);

        $markdown = $converter->convert($html);

        // Links to the old site become links within this one.
        $markdown = (string) preg_replace_callback(
            '#\]\((?:https?://(?:www\.|development\.)?'.preg_quote((string) parse_url($this->base, PHP_URL_HOST), '#').')?(/[^)\s]*)\)#',
            function (array $match): string {
                $path = Redirect::normalise((string) parse_url($match[1], PHP_URL_PATH));

                if (str_starts_with($path, '/wp-content/')) {
                    return "]({$this->base}{$match[1]})";
                }

                return ']('.(Redirect::where('from_path', $path)->value('to_path') ?? $match[1]).')';
            },
            $markdown,
        );

        // Bank details were printed on the old site. They never belong on a public page.
        $markdown = (string) preg_replace('/^.*\b\d{2}-\d{2}-\d{2}\b.*$/m', '', $markdown);
        $markdown = (string) preg_replace('/^.*\b(sort code|account (number|no))\b.*$/im', '', $markdown);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $markdown));
    }
}

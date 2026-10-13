<?php

namespace App\Support;

/**
 * Everything a public page tells search engines and AI assistants about
 * itself. Every public controller action builds one; the root Blade view
 * prints it as meta tags and JSON-LD, so it is in the HTML with or without
 * JavaScript.
 */
final class Seo
{
    /**
     * @param  string  $title  the page's own title, without the site name
     * @param  array<int, array{0: string, 1: string}>  $breadcrumbs  [label, path] pairs below Home
     * @param  array<int, array<string, mixed>>  $schema  extra schema.org nodes for this page
     * @param  string|null  $image  absolute URL or site path of the sharing image
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $breadcrumbs = [],
        public array $schema = [],
        public ?string $image = null,
        public string $type = 'website',
        public bool $index = true,
        public bool $isHome = false,
    ) {}

    public function fullTitle(): string
    {
        return $this->isHome ? $this->title : "{$this->title} | ".config('site.name');
    }

    public function description(): string
    {
        return Markdown::text($this->description, 200);
    }

    /**
     * The address search engines should index: the current path on the
     * site's own host, with no query string.
     */
    public function canonical(): string
    {
        return url(request()->getPathInfo() === '/' ? '/' : rtrim(request()->getPathInfo(), '/'));
    }

    public function imageUrl(): string
    {
        return url($this->image ?? '/images/brand/social.png');
    }

    /**
     * The JSON-LD graph: the club and the website on every page, then the
     * breadcrumb trail and whatever the page adds.
     *
     * @return array<string, mixed>
     */
    public function graph(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                Schema::club(),
                Schema::website(),
                Schema::breadcrumbs($this->breadcrumbs),
                ...$this->schema,
            ])),
        ];
    }

    /**
     * What the browser needs to keep the tab title and description right
     * when moving between pages without a full reload.
     *
     * @return array{title: string, description: string, canonical: string, breadcrumbs: array<int, array{label: string, href: string}>}
     */
    public function forClient(): array
    {
        return [
            'title' => $this->fullTitle(),
            'description' => $this->description(),
            'canonical' => $this->canonical(),
            'breadcrumbs' => array_map(
                fn (array $crumb): array => ['label' => $crumb[0], 'href' => $crumb[1]],
                $this->breadcrumbs,
            ),
        ];
    }
}

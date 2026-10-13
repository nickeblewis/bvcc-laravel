<?php

namespace App\Http\Controllers\Site;

use App\Enums\PageSection;
use App\Models\Faq;
use App\Models\Page;
use App\Support\Present;
use App\Support\Schema;
use App\Support\Seo;
use Inertia\Response;

/**
 * Editable content pages: /paddle/…, /join/… and /club/….
 */
class PageController extends SiteController
{
    public function show(PageSection $section, string $slug): Response
    {
        $page = Page::where('is_published', true)->where('section', $section)->where('slug', $slug)->firstOrFail();

        $faqs = $page->faq_category
            ? Faq::published()->where('category', $page->faq_category)->get()
            : collect();

        $parent = [$section->getLabel(), "/{$section->value}"];

        return $this->page('ContentPage', [
            'page' => Present::page($page),
            'faqs' => Present::faqs($faqs),
            'section' => ['label' => $parent[0], 'url' => $parent[1]],
            'siblings' => Page::published()->where('section', $section)->whereKeyNot($page->id)->get()
                ->map(fn (Page $p): array => ['title' => $p->title, 'url' => $p->path()]),
        ], new Seo(
            title: $page->title,
            description: $page->getAttribute('meta_description') ?: (string) $page->summary,
            breadcrumbs: [$parent, [$page->title, $page->path()]],
            schema: array_filter([Schema::faqPage($faqs)]),
            image: Present::image($page->getAttribute('hero_image'))['src'] ?? null,
        ), markdownUrl: $page->path().'.md');
    }
}

<?php

namespace App\Http\Controllers\Site;

use App\Enums\PageSection;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Discipline;
use App\Models\Event;
use App\Models\Page;
use App\Support\MarkdownExport;
use Illuminate\Http\Response;

/**
 * The ".md" twin of a content page: the same content as plain Markdown.
 */
class MarkdownController extends Controller
{
    public function page(string $section, string $slug): Response
    {
        $section = PageSection::from($section);

        if ($section === PageSection::Paddle && $discipline = Discipline::where('is_published', true)->where('slug', $slug)->first()) {
            return $this->markdown(MarkdownExport::discipline($discipline), "/paddle/{$slug}");
        }

        $page = Page::where('is_published', true)->where('section', $section)->where('slug', $slug)->firstOrFail();

        return $this->markdown(MarkdownExport::page($page), $page->path());
    }

    public function event(string $slug): Response
    {
        $event = Event::published()->where('slug', $slug)->with(['venue', 'category', 'discipline'])->firstOrFail();

        return $this->markdown(MarkdownExport::event($event), "/events/{$slug}");
    }

    public function article(string $slug): Response
    {
        return $this->markdown(MarkdownExport::article(Article::published()->where('slug', $slug)->firstOrFail()), "/news/{$slug}");
    }

    private function markdown(string $content, string $canonicalPath): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            // The HTML page is the one to index; this is the same content.
            'Link' => '<'.url($canonicalPath).'>; rel="canonical"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}

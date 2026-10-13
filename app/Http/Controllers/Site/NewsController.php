<?php

namespace App\Http\Controllers\Site;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Support\Markdown;
use App\Support\Present;
use App\Support\Schema;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Response;

class NewsController extends SiteController
{
    public function index(Request $request): Response
    {
        $category = $request->filled('category')
            ? ArticleCategory::where('slug', $request->string('category'))->first()
            : null;

        $articles = Article::published()->with('category')
            ->when($category, fn (Builder $q) => $q->where('article_category_id', $category?->id))
            ->paginate(9)
            ->withQueryString();

        return $this->page('news/Index', [
            'articles' => $articles->getCollection()->map(Present::articleCard(...)),
            'pagination' => [
                'current' => $articles->currentPage(),
                'last' => $articles->lastPage(),
                'prev' => $articles->previousPageUrl(),
                'next' => $articles->nextPageUrl(),
            ],
            'categories' => ArticleCategory::has('articles')->orderBy('name')->get(['name', 'slug']),
            'current' => $category?->slug,
        ], new Seo(
            title: $category ? "{$category->name}: club news" : 'Club news',
            description: 'News from '.config('site.name').': session updates, trip reports and what is coming up.',
            breadcrumbs: [['News', '/news']],
            // Filtered and later pages are for people, not the index.
            index: $category === null && $articles->currentPage() === 1,
        ));
    }

    public function show(string $slug): Response
    {
        $article = Article::published()->where('slug', $slug)->with('category')->firstOrFail();

        return $this->page('news/Show', [
            'article' => [
                ...Present::articleCard($article),
                'html' => Markdown::html($article->body),
                'author' => $article->getAttribute('author_name'),
            ],
            'more' => Article::published()->whereKeyNot($article->id)->with('category')->limit(3)->get()->map(Present::articleCard(...)),
        ], new Seo(
            title: $article->title,
            description: $article->excerpt ?: Markdown::text($article->body),
            breadcrumbs: [['News', '/news'], [$article->title, "/news/{$article->slug}"]],
            schema: [Schema::article($article)],
            image: Present::image($article->getAttribute('hero_image'))['src'] ?? null,
            type: 'article',
        ), markdownUrl: "/news/{$article->slug}.md");
    }

    public function feed(): HttpResponse
    {
        return response()
            ->view('feeds.news', ['articles' => Article::published()->limit(20)->get()])
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}

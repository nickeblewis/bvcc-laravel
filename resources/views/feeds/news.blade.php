{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('site.name') }} news</title>
        <link>{{ url('/news') }}</link>
        <description>News from {{ config('site.name') }}</description>
        <language>en-gb</language>
        <atom:link href="{{ url('/news/feed.xml') }}" rel="self" type="application/rss+xml" />
@foreach ($articles as $article)
        <item>
            <title>{{ $article->title }}</title>
            <link>{{ url("/news/{$article->slug}") }}</link>
            <guid isPermaLink="true">{{ url("/news/{$article->slug}") }}</guid>
            <pubDate>{{ $article->published_at->toRssString() }}</pubDate>
            <description>{{ $article->excerpt ?: \App\Support\Markdown::text($article->body, 300) }}</description>
        </item>
@endforeach
    </channel>
</rss>

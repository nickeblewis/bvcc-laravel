<!DOCTYPE html>
<html lang="en-GB" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #f6fbfa;
            }

            html.dark {
                background-color: #04121a;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/images/brand/icon-192.png" type="image/png" sizes="192x192">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#071c27">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])

        @isset($seo)
            {{-- Public pages: see App\Support\Seo. Printed here, not from Vue, so it is in the HTML with or without JavaScript. --}}
            <title>{{ $seo->fullTitle() }}</title>
            <meta name="description" content="{{ $seo->description() }}">
            <link rel="canonical" href="{{ $seo->canonical() }}">
            <meta name="robots" content="{{ $seo->index && app()->environment('production') ? 'index, follow, max-image-preview:large' : 'noindex, nofollow' }}">

            <meta property="og:site_name" content="{{ config('site.name') }}">
            <meta property="og:locale" content="en_GB">
            <meta property="og:type" content="{{ $seo->type === 'article' ? 'article' : 'website' }}">
            <meta property="og:title" content="{{ $seo->title }}">
            <meta property="og:description" content="{{ $seo->description() }}">
            <meta property="og:url" content="{{ $seo->canonical() }}">
            <meta property="og:image" content="{{ $seo->imageUrl() }}">
            <meta name="twitter:card" content="summary_large_image">

            <link rel="alternate" type="application/rss+xml" title="{{ config('site.name') }} news" href="{{ url('/news/feed.xml') }}">
            @isset($markdownUrl)
                <link rel="alternate" type="text/markdown" href="{{ url($markdownUrl) }}">
            @endisset

            <script type="application/ld+json">{!! json_encode($seo->graph(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @else
            <x-inertia::head>
                <title>{{ config('app.name', 'Laravel') }}</title>
            </x-inertia::head>
        @endisset
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>

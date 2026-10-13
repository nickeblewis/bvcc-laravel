<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Page not found | {{ config('site.name') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #071c27; color: #fff; font-family: ui-sans-serif, system-ui, sans-serif; line-height: 1.6; text-align: center; }
        main { padding: 2rem 1.25rem; max-width: 34rem; }
        img { height: 5rem; width: auto; border-radius: .75rem; }
        h1 { font-size: clamp(1.9rem, 1.4rem + 3vw, 3rem); line-height: 1.1; margin: 1.25rem 0 .75rem; }
        p { color: #aeeae6; font-size: 1.125rem; margin: 0 0 1.5rem; }
        nav { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: center; }
        a { display: inline-flex; align-items: center; min-height: 2.75rem; padding: 0 1.25rem; border-radius: 999px; font-weight: 600; text-decoration: none; color: #fff; border: 2px solid currentColor; }
        a.gold { background: #ffd43b; border-color: #ffd43b; color: #04121a; }
        a:focus-visible { outline: 3px solid #ffd43b; outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <img src="/images/brand/logo.png" alt="" width="400" height="293">
        <h1>We have paddled past that page</h1>
        <p>The page you were looking for is not here. It may have moved when we rebuilt the website.</p>
        <nav aria-label="Where to go next">
            <a class="gold" href="/">Home</a>
            <a href="/events">Events and trips</a>
            <a href="/contact">Contact us</a>
        </nav>
    </main>
</body>
</html>

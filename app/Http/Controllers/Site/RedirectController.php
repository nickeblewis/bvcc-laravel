<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The last route: when nothing else matches, see whether the address is one
 * the old WordPress site used and send the visitor (and the search engine)
 * to its new home with a permanent redirect.
 */
class RedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $path = Redirect::normalise($request->getPathInfo());

        if ($redirect = Redirect::where('from_path', $path)->first()) {
            $redirect->increment('hits');

            return redirect($redirect->to_path, 301);
        }

        // Old news posts: /news/2026/03/02/the-slug
        if (preg_match('#^/news/\d{4}/\d{2}/\d{2}/([a-z0-9-]+)$#', $path, $match)) {
            return redirect(Article::where('slug', $match[1])->exists() ? "/news/{$match[1]}" : '/news', 301);
        }

        // Old news categories: /news/category/club-trips
        if (preg_match('#^/news/category/([a-z0-9-]+)$#', $path, $match)) {
            return redirect("/news?category={$match[1]}", 301);
        }

        // The old site had hundreds of event, venue and category pages;
        // send them to the calendar or the venues list.
        if (preg_match('#^/events/(search-events|categories)/#', $path)) {
            return redirect('/events', 301);
        }

        if (preg_match('#^/events/locations/#', $path)) {
            return redirect('/venues', 301);
        }

        abort(404);
    }
}

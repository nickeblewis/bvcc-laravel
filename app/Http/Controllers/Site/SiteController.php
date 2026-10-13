<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

abstract class SiteController extends Controller
{
    /**
     * Render a public page. The Seo object is required on purpose: every
     * public page declares its title, description and structured data.
     *
     * @param  array<string, mixed>  $props
     */
    protected function page(string $component, array $props, Seo $seo, ?string $markdownUrl = null): Response
    {
        return Inertia::render("site/{$component}", [...$props, 'meta' => $seo->forClient()])
            ->withViewData(['seo' => $seo, 'markdownUrl' => $markdownUrl]);
    }
}

<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'site' => fn (): array => $this->site(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * What the public layout needs on every page: the club's identity and
     * the navigation. Kept small, as it is sent with every response.
     *
     * @return array<string, mixed>
     */
    private function site(): array
    {
        return [
            'name' => config('site.name'),
            'shortName' => config('site.short_name'),
            'tagline' => config('site.tagline'),
            'email' => config('site.email'),
            'founded' => config('site.founded'),
            'governingBody' => config('site.governing_body'),
            'social' => array_filter((array) config('site.social')),
            'nav' => [
                ['label' => 'Paddle with us', 'href' => '/paddle'],
                ['label' => 'Events and trips', 'href' => '/events'],
                ['label' => 'Join', 'href' => '/join'],
                ['label' => 'News', 'href' => '/news'],
                ['label' => 'Club', 'href' => '/club'],
                ['label' => 'Contact', 'href' => '/contact'],
            ],
            'footer' => [
                'Paddling' => [
                    ['label' => 'Weekly sessions', 'href' => '/paddle/weekly-sessions'],
                    ['label' => 'Beginners and coaching', 'href' => '/paddle/beginners'],
                    ['label' => 'Where we paddle', 'href' => '/venues'],
                    ['label' => 'Events calendar', 'href' => '/events'],
                    ['label' => 'Equipment hire', 'href' => '/join/hire'],
                ],
                'Club' => [
                    ['label' => 'Membership and fees', 'href' => '/join'],
                    ['label' => 'About and committee', 'href' => '/club'],
                    ['label' => 'Documents and policies', 'href' => '/club/documents'],
                    ['label' => 'Safeguarding', 'href' => '/club/safeguarding'],
                    ['label' => 'Privacy policy', 'href' => '/club/privacy-policy'],
                    ['label' => 'FAQs', 'href' => '/faqs'],
                ],
            ],
        ];
    }
}

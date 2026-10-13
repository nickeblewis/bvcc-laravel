<?php

namespace App\Http\Controllers\Site;

use App\Models\Faq;
use App\Support\Present;
use App\Support\Schema;
use App\Support\Seo;
use Inertia\Response;

class FaqController extends SiteController
{
    public function __invoke(): Response
    {
        $faqs = Faq::published()->get();

        return $this->page('Faqs', [
            'groups' => collect(Faq::CATEGORIES)
                ->map(fn (string $label, string $key): array => [
                    'label' => $label,
                    'slug' => $key,
                    'faqs' => Present::faqs($faqs->where('category', $key)),
                ])
                ->filter(fn (array $group): bool => $group['faqs'] !== [])
                ->values(),
        ], new Seo(
            title: 'Frequently asked questions',
            description: 'Answers about trying kayaking and canoeing with '.config('site.short_name').': what it costs, what to bring, when we meet, membership, booking and kit.',
            breadcrumbs: [['FAQs', '/faqs']],
            schema: array_filter([Schema::faqPage($faqs)]),
        ));
    }
}

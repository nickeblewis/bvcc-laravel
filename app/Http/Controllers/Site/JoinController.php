<?php

namespace App\Http\Controllers\Site;

use App\Enums\FeeBand;
use App\Enums\FeeType;
use App\Enums\PageSection;
use App\Mail\HireRequestMail;
use App\Models\Faq;
use App\Models\HireRequest;
use App\Models\Page;
use App\Services\FeeCalculator;
use App\Support\Money;
use App\Support\Present;
use App\Support\Schema;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Response;

class JoinController extends SiteController
{
    public function index(FeeCalculator $fees): Response
    {
        $faqs = Faq::published()->where('category', 'membership')->get();

        return $this->page('join/Index', [
            'tiers' => Present::tiers(),
            // The fee table is built from the same rates the booking system charges.
            'feeTable' => [
                'columns' => array_map(fn (FeeType $type): string => $type->getLabel(), FeeType::standard()),
                'rows' => array_map(fn (FeeBand $band): array => [
                    'band' => $band->getLabel(),
                    'prices' => array_map(
                        fn (FeeType $type): string => match ($pence = $fees->rate($band, $type)) {
                            null => 'n/a',
                            0 => 'Free',
                            default => Money::format($pence),
                        },
                        FeeType::standard(),
                    ),
                ], FeeBand::cases()),
            ],
            'pages' => Page::published()->where('section', PageSection::Join)->get()
                ->map(fn (Page $p): array => ['title' => $p->title, 'summary' => $p->summary, 'url' => $p->path()]),
            'faqs' => Present::faqs($faqs),
            'membershipsEmail' => config('site.memberships_email'),
        ], new Seo(
            title: 'Join the club: membership and fees',
            description: 'How to join '.config('site.name').', what membership costs, and what you pay per session. Come and try a session first: non-member registration is free.',
            breadcrumbs: [['Join', '/join']],
            schema: array_filter([Schema::faqPage($faqs)]),
        ));
    }

    public function hire(): Response
    {
        $page = Page::where('section', PageSection::Join)->where('slug', 'hire')->where('is_published', true)->firstOrFail();
        $faqs = Faq::published()->where('category', 'kit')->get();

        return $this->page('join/Hire', [
            'page' => Present::page($page),
            'faqs' => Present::faqs($faqs),
            'options' => [
                'items' => HireRequest::ITEMS,
                'craftTypes' => HireRequest::CRAFT_TYPES,
                'useTypes' => HireRequest::USE_TYPES,
            ],
            'sent' => (bool) session('hire_sent'),
        ], new Seo(
            title: 'Equipment hire',
            description: $page->getAttribute('meta_description') ?: (string) $page->summary,
            breadcrumbs: [['Join', '/join'], ['Equipment hire', '/join/hire']],
            schema: array_filter([Schema::faqPage($faqs)]),
        ), markdownUrl: '/join/hire.md');
    }

    public function storeHire(Request $request): RedirectResponse
    {
        // A field people never see; bots fill it in.
        if (filled($request->input('website'))) {
            return back()->with('hire_sent', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'use_type' => ['required', Rule::in(array_keys(HireRequest::USE_TYPES))],
            'collect_on' => ['required', 'date', 'after_or_equal:today'],
            'return_on' => ['required', 'date', 'after_or_equal:collect_on', 'before_or_equal:'.$request->date('collect_on')?->addWeeks(4)->toDateString()],
            'participants' => ['required', 'integer', 'min:1', 'max:30'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => [Rule::in(HireRequest::ITEMS)],
            'craft_type' => ['nullable', Rule::in(HireRequest::CRAFT_TYPES)],
            'intended_use' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
        ], [
            'return_on.before_or_equal' => 'Kit can be borrowed for up to 4 weeks.',
            'items.required' => 'Choose at least one item.',
            'terms.accepted' => 'Please confirm you have read the hire terms.',
        ]);

        unset($data['terms']);

        $hire = HireRequest::create($data);

        Mail::to(config('site.equipment_email'))->send(new HireRequestMail($hire));

        return back()->with('hire_sent', true);
    }
}

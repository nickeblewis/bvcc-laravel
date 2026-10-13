<?php

namespace App\Http\Controllers\Site;

use App\Enums\PageSection;
use App\Models\CommitteeRole;
use App\Models\Document;
use App\Models\Page;
use App\Support\Seo;
use Inertia\Response;

class ClubController extends SiteController
{
    public function index(): Response
    {
        return $this->page('club/Index', [
            'pages' => Page::published()->where('section', PageSection::Club)->get()
                ->map(fn (Page $p): array => ['title' => $p->title, 'summary' => $p->summary, 'url' => $p->path()]),
            'roles' => CommitteeRole::where('is_listed', true)->orderBy('sort')->get()
                ->map(fn (CommitteeRole $role): array => [
                    'title' => $role->title,
                    'holder' => $role->holder_name,
                    'email' => $role->email,
                    'description' => $role->getAttribute('description'),
                ]),
            'documentCount' => Document::where('is_public', true)->count(),
        ], new Seo(
            title: 'About the club',
            description: config('site.name').' is a volunteer-run canoe and kayak club founded in '.config('site.founded').' and based at Aldershot Lido. Meet the committee and read how the club is run.',
            breadcrumbs: [['Club', '/club']],
        ));
    }

    public function documents(): Response
    {
        $documents = Document::where('is_public', true)->orderByDesc('document_date')->orderBy('title')->get();

        return $this->page('club/Documents', [
            'groups' => collect(Document::CATEGORIES)
                ->map(fn (string $label, string $key): array => [
                    'label' => $label,
                    'documents' => $documents->where('category', $key)->values()->map(fn (Document $d): array => [
                        'title' => $d->title,
                        'url' => $d->url(),
                        'date' => $d->document_date?->format('j M Y'),
                        'type' => strtoupper(pathinfo($d->file, PATHINFO_EXTENSION)),
                    ]),
                ])
                ->filter(fn (array $group): bool => $group['documents']->isNotEmpty())
                ->values(),
            'pages' => Page::published()->where('section', PageSection::Club)->get()
                ->map(fn (Page $p): array => ['title' => $p->title, 'summary' => $p->summary, 'url' => $p->path()]),
        ], new Seo(
            title: 'Documents and policies',
            description: 'Club policies, the constitution, risk assessments and meeting minutes for '.config('site.name').'.',
            breadcrumbs: [['Club', '/club'], ['Documents and policies', '/club/documents']],
        ));
    }
}

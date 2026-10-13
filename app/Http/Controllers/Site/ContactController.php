<?php

namespace App\Http\Controllers\Site;

use App\Mail\EnquiryMail;
use App\Models\CommitteeRole;
use App\Models\Enquiry;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Response;

class ContactController extends SiteController
{
    public function show(Request $request): Response
    {
        $topics = CommitteeRole::where('takes_enquiries', true)->whereNotNull('email')->orderBy('sort')->get();

        return $this->page('Contact', [
            'topics' => $topics->map(fn (CommitteeRole $role): array => [
                'id' => $role->id,
                'title' => $role->title,
                'description' => $role->getAttribute('description'),
            ]),
            'selected' => $topics->first(fn (CommitteeRole $role): bool => str($role->title)->slug()->is($request->string('topic')))?->id,
            'email' => config('site.email'),
            'social' => array_filter((array) config('site.social')),
            'sent' => (bool) session('enquiry_sent'),
        ], new Seo(
            title: 'Contact us',
            description: 'Get in touch with '.config('site.name').': ask about trying a session, membership, bookings, canoe polo or equipment hire.',
            breadcrumbs: [['Contact', '/contact']],
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        // A field people never see; bots fill it in.
        if (filled($request->input('website'))) {
            return back()->with('enquiry_sent', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'committee_role_id' => ['nullable', Rule::exists('committee_roles', 'id')->where('takes_enquiries', true)],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $enquiry = Enquiry::create($data);

        Mail::to($enquiry->role->email ?? config('site.email'))->send(new EnquiryMail($enquiry));

        return back()->with('enquiry_sent', true);
    }
}

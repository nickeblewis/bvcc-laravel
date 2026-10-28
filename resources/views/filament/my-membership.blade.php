@php
    $member = $this->member();
    $membership = $this->membership();
@endphp

<x-filament-panels::page>
    <x-filament::section heading="Membership">
        @if ($membership)
            <dl style="display: grid; grid-template-columns: max-content 1fr; gap: .5rem 1.5rem;">
                <dt>Type</dt>
                <dd>{{ $membership->tier->name }}</dd>
                <dt>Status</dt>
                <dd>
                    <x-filament::badge :color="$membership->status->getColor()" style="display: inline-flex;">
                        {{ $membership->status->getLabel() }}
                    </x-filament::badge>
                </dd>
                <dt>Runs</dt>
                <dd>{{ $membership->starts_on->format('j F Y') }} to {{ $membership->expires_on->format('j F Y') }}</dd>
                <dt>Price</dt>
                <dd>{{ $this->money($membership->price_pence) }}</dd>
                @if ($membership->balancePence() > 0)
                    <dt>Still to pay</dt>
                    <dd>{{ $this->money($membership->balancePence()) }}</dd>
                @endif
            </dl>

            @if ($membership->balancePence() > 0)
                <p style="margin-top: 1rem; font-size: .875rem; opacity: .8;">
                    Your membership starts once it is paid. The membership secretary will be in touch with how to pay,
                    or email <a href="mailto:{{ config('site.memberships_email') }}" style="text-decoration: underline;">{{ config('site.memberships_email') }}</a>.
                </p>
            @endif
        @else
            <p>You have no membership yet. Email <a href="mailto:{{ config('site.memberships_email') }}" style="text-decoration: underline;">{{ config('site.memberships_email') }}</a> to join.</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="Your details" description="To change any of these, email the membership secretary.">
        <dl style="display: grid; grid-template-columns: max-content 1fr; gap: .5rem 1.5rem;">
            <dt>Name</dt>
            <dd>{{ $member->name }}</dd>
            <dt>Email</dt>
            <dd>{{ $member->email }}</dd>
            <dt>Phone</dt>
            <dd>{{ $member->getAttribute('phone') ?? '—' }}</dd>
            <dt>Date of birth</dt>
            <dd>{{ $member->date_of_birth?->format('j F Y') ?? '—' }}</dd>
            <dt>Address</dt>
            <dd>
                {{ collect([$member->getAttribute('address_line_1'), $member->getAttribute('address_line_2'), $member->getAttribute('town'), $member->getAttribute('postcode')])->filter()->implode(', ') ?: '—' }}
            </dd>
            <dt>Paddle UK number</dt>
            <dd>{{ $member->getAttribute('paddle_uk_number') ?? '—' }}</dd>
        </dl>
    </x-filament::section>
</x-filament-panels::page>

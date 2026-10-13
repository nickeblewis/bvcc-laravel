<x-mail::message>
# {{ $event->title }} is cancelled

Hello {{ $booking->contact_name }},

We are sorry to say that **{{ $event->title }}** on {{ $event->starts_at->format('l j F Y') }} has been cancelled.

@if ($event->cancel_reason)
{{ $event->cancel_reason }}
@endif

@if ($paid)
You have paid {{ $paid }} for this booking. We will be in touch about a refund.
@endif

Thanks,<br>
{{ config('site.name') }}
</x-mail::message>

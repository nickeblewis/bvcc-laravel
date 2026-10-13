<x-mail::message>
# Payment due

Hello {{ $booking->contact_name }},

You have a place booked on **{{ $event->title }}** on {{ $event->starts_at->format('l j F Y') }}, and **{{ $balance }}** is still to pay.

{{ config('site.payment_instructions') }}

If you can no longer come, please reply and let us know so we can offer the place to someone else.

Thanks,<br>
{{ config('site.name') }}
</x-mail::message>

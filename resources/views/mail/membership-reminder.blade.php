<x-mail::message>
@if ($renewal)
# Your membership is due for renewal

Hello {{ $membership->lead->first_name }},

Your **{{ $membership->tier->name }}** membership runs until {{ $membership->expires_on->format('j F Y') }}. To keep paddling with us, reply to this email and we will set up your renewal.
@else
# Payment due for your membership

Hello {{ $membership->lead->first_name }},

Your **{{ $membership->tier->name }}** membership is waiting for payment, and **{{ $balance }}** is still to pay.

{{ config('site.payment_instructions') }}
@endif

Thanks,<br>
{{ config('site.name') }}
</x-mail::message>

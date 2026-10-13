<x-mail::message>
# Equipment hire request

**From:** {{ $hireRequest->name }} ({{ $hireRequest->email }}@if ($hireRequest->phone), {{ $hireRequest->phone }}@endif)

**Use:** {{ \App\Models\HireRequest::USE_TYPES[$hireRequest->use_type] ?? $hireRequest->use_type }}

**Dates:** {{ $hireRequest->collect_on->format('j M Y') }} to {{ $hireRequest->return_on->format('j M Y') }}

**People:** {{ $hireRequest->participants }}

**Items:** {{ implode(', ', $hireRequest->items) }}@if ($hireRequest->craft_type) ({{ $hireRequest->craft_type }})@endif

@if ($hireRequest->intended_use)
**Intended use:** {{ $hireRequest->intended_use }}
@endif

@if ($hireRequest->notes)
**Notes:** {{ $hireRequest->notes }}
@endif
</x-mail::message>

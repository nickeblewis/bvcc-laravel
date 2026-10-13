<x-mail::message>
# Website enquiry

**From:** {{ $enquiry->name }} ({{ $enquiry->email }}@if ($enquiry->phone), {{ $enquiry->phone }}@endif)

@if ($enquiry->role)
**For:** {{ $enquiry->role->title }}
@endif

{{ $enquiry->message }}
</x-mail::message>

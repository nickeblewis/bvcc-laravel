<x-filament-panels::page>
    @unless ($this->showsMedical())
        <p style="font-size: .875rem; opacity: .7;">
            Emergency contacts and medical notes are shown to welfare staff and to this event's leader.
        </p>
    @endunless

    {{ $this->table }}

    {{-- Print just the register: no sidebar, top bar or buttons. --}}
    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header-actions, .fi-ac, .fi-ta-header-toolbar, .fi-breadcrumbs { display: none !important; }
            .fi-main { padding: 0 !important; max-width: none !important; }
            .fi-ta-ctn { box-shadow: none !important; border: 0 !important; }
            body { background: #fff !important; }
        }
    </style>
</x-filament-panels::page>

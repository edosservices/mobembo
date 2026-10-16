@props(['tone' => 'light', 'withName' => true])

@php
    $onDark = $tone === 'dark';
    $sheet = $onDark ? '#FFFFFF' : '#DBEAFE';
    $fold = $onDark ? '#BFDBFE' : '#3B82F6';
    $ink = $onDark ? '#2563EB' : '#2563EB';
    $word = $onDark ? '#FFFFFF' : '#1F2937';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <svg viewBox="0 0 48 48" class="h-9 w-9 shrink-0" aria-hidden="true">
        <rect x="8" y="6" width="28" height="36" rx="4" fill="{{ $sheet }}" />
        <path d="M28 6h8a4 4 0 0 1 4 4v6H28V6Z" fill="{{ $fold }}" />
        <circle cx="22" cy="20" r="4.2" fill="{{ $ink }}" />
        <path d="M13.5 33.5c1.4-4.4 4.6-6.6 8.5-6.6s7.1 2.2 8.5 6.6" fill="{{ $ink }}" />
        <rect x="14" y="35" width="16" height="2" rx="1" fill="{{ $onDark ? '#93C5FD' : '#93C5FD' }}" />
    </svg>
    @if ($withName)
        <span class="font-display text-lg font-semibold tracking-tight" style="color: {{ $word }}">CVora</span>
    @endif
</span>

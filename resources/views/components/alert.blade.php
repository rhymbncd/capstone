@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $variants = [
        'success' => [
            'bg' => 'bg-success-tint', 'text' => 'text-success',
            'icon' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        ],
        'warning' => [
            'bg' => 'bg-warning-tint', 'text' => 'text-warning',
            'icon' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        ],
        'error' => [
            'bg' => 'bg-error-tint', 'text' => 'text-error',
            'icon' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        ],
        'info' => [
            'bg' => 'bg-primary-tint', 'text' => 'text-primary',
            'icon' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        ],
    ];

    $v = $variants[$variant] ?? $variants['info'];
@endphp

<div {{ $attributes->merge(['class' => "flex gap-3 rounded-md p-4 {$v['bg']}"]) }} role="alert">
    <svg class="h-5 w-5 flex-shrink-0 {{ $v['text'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        {!! $v['icon'] !!}
    </svg>
    <div class="text-[15px]">
        @if($title)
            <p class="mb-0.5 font-semibold text-neutral-900">{{ $title }}</p>
        @endif
        <div class="text-neutral-700">{{ $slot }}</div>
    </div>
</div>

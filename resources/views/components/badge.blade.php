@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'primary' => 'bg-primary-tint text-primary',
        'success' => 'bg-success-tint text-success',
        'warning' => 'bg-warning-tint text-warning',
        'error' => 'bg-error-tint text-error',
        'neutral' => 'bg-neutral-50 text-neutral-700',
    ];

    $classes = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-pill px-2.5 py-1 text-[13px] font-semibold $classes"]) }}>
    {{ $slot }}
</span>

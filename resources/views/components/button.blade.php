@props([
    'variant' => 'primary',
    'loading' => false,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-md text-[15px] font-semibold '
        . 'transition-colors duration-150 disabled:opacity-60 disabled:cursor-not-allowed '
        . 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2';

    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary-hover focus-visible:outline-primary',
        'secondary' => 'bg-white text-neutral-700 border border-neutral-200 hover:bg-neutral-50 focus-visible:outline-primary',
        'danger' => 'bg-error-tint text-error border border-error/25 hover:bg-error/10 focus-visible:outline-error',
        // Sign-in/sign-up pages only (see resources/css/app.css): blue for
        // the sign-in action, green for sign-up — matching what those
        // colors already mean on the homepage hero. Not for use elsewhere.
        'signin' => 'bg-[var(--auth-signin)] text-white hover:bg-[var(--auth-signin-hover)] focus-visible:outline-[var(--auth-signin)]',
        'signup' => 'bg-[var(--auth-signup)] text-white hover:bg-[var(--auth-signup-hover)] focus-visible:outline-[var(--auth-signup)]',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

<button
    {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}
    @disabled($loading)
    @if($loading) aria-busy="true" @endif
>
    @if($loading)
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    @endif
    {{ $slot }}
</button>

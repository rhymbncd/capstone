@props([
    'label' => null,
    'name' => null,
    'error' => null,
    'hint' => null,
    'id' => null,
])

@php
    $selectId = $id ?? $name ?? 'select-'.str()->random(6);
    $hasError = filled($error);

    $classes = 'h-10 w-full cursor-pointer rounded-md border bg-white px-3 text-[15px] text-neutral-900 '
        . 'transition-colors duration-150 outline-none focus-visible:ring-2 focus-visible:ring-primary/15 '
        . 'disabled:bg-neutral-50 disabled:text-neutral-500 disabled:cursor-not-allowed '
        . ($hasError ? 'border-error focus-visible:border-error' : 'border-neutral-200 focus-visible:border-primary');
@endphp

<div>
    @if($label)
        <label for="{{ $selectId }}" class="mb-1.5 block text-[13px] font-medium text-neutral-700">{{ $label }}</label>
    @endif

    <select
        id="{{ $selectId }}"
        @if($name) name="{{ $name }}" @endif
        {{ $attributes->merge(['class' => $classes]) }}
        @if($hasError) aria-invalid="true" aria-describedby="{{ $selectId }}-error" @endif
    >
        {{ $slot }}
    </select>

    @if($hasError)
        <p id="{{ $selectId }}-error" class="mt-1.5 text-[13px] text-error">{{ $error }}</p>
    @elseif($hint)
        <p class="mt-1.5 text-[13px] text-neutral-500">{{ $hint }}</p>
    @endif
</div>

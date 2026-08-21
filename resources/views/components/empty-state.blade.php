@props([
    'icon' => '📋',
    'title' => 'Nothing here yet',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16 text-center']) }}>
    <div class="mb-3 text-4xl" aria-hidden="true">{{ $icon }}</div>
    <p class="text-base font-semibold text-neutral-900">{{ $title }}</p>
    @if($description)
        <p class="mt-1 max-w-xs text-[13px] text-neutral-500">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>

@props([
    'title' => null,
    'description' => null,
    'padding' => 'p-6',
])

<div {{ $attributes->merge(['class' => "rounded-lg border border-neutral-200 bg-white $padding"]) }}>
    @if($title || $description)
        <div class="mb-4">
            @if($title)
                <h3 class="text-base font-semibold text-neutral-900">{{ $title }}</h3>
            @endif
            @if($description)
                <p class="mt-0.5 text-[13px] text-neutral-500">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>

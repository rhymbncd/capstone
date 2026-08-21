@props([
    'id' => null,
    'title' => null,
])

{{--
    Compatible with the openModal(id)/closeModal(id) helpers already used
    across the dashboards (they toggle an "open" class on this element) —
    no new JS needed to use this component.
--}}
<div
    id="{{ $id }}"
    class="group fixed inset-0 z-[1000] flex items-center justify-center p-4
        bg-neutral-900/40 opacity-0 pointer-events-none transition-opacity duration-200
        [&.open]:opacity-100 [&.open]:pointer-events-auto"
>
    <div class="flex max-h-[85vh] w-full max-w-lg scale-95 flex-col rounded-lg bg-white
        shadow-overlay transition-transform duration-200 group-[.open]:scale-100">
        <div class="flex items-center justify-between border-b border-neutral-200 px-6 py-4">
            <h2 class="text-base font-semibold text-neutral-900">{{ $title }}</h2>
            <button
                type="button"
                onclick="closeModal('{{ $id }}')"
                aria-label="Close"
                class="rounded-md p-1 text-neutral-500 transition-colors duration-150 hover:text-neutral-900
                    focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="overflow-y-auto px-6 py-4">
            {{ $slot }}
        </div>
    </div>
</div>

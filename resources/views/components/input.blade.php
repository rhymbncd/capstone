@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'error' => null,
    'hint' => null,
    'id' => null,
])

@php
    $inputId = $id ?? $name ?? 'input-'.str()->random(6);
    $hasError = filled($error);
    $isPassword = $type === 'password';

    $classes = 'h-10 w-full rounded-md border px-3 text-[15px] text-neutral-900 placeholder:text-neutral-500 '
        . 'transition-colors duration-150 outline-none focus:ring-2 focus:ring-primary/15 '
        . 'disabled:bg-neutral-50 disabled:text-neutral-500 disabled:cursor-not-allowed '
        . 'read-only:bg-neutral-50 read-only:text-neutral-500 read-only:cursor-not-allowed read-only:focus:ring-0 '
        . ($isPassword ? 'pr-10 ' : '')
        . ($hasError ? 'border-error focus:border-error' : 'border-neutral-200 focus:border-primary');
@endphp

<div>
    @if($label)
        <label for="{{ $inputId }}" class="mb-1.5 block text-[13px] font-medium text-neutral-700">{{ $label }}</label>
    @endif

    <div @class(['relative' => $isPassword])>
        <input
            id="{{ $inputId }}"
            @if($name) name="{{ $name }}" @endif
            type="{{ $type }}"
            {{ $attributes->merge(['class' => $classes]) }}
            @if($hasError) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
        >

        @if($isPassword)
            {{-- Toggle logic lives inline below (@once) rather than in a
                 dashboard-specific JS file, since this component renders on
                 pages (dashboards, login, reset-password) that don't all
                 share a common bundle. Delegated on `document` via
                 data-action so it works no matter how many times this
                 component renders on the page. --}}
            <button
                type="button"
                data-action="toggle-password"
                aria-label="Show password"
                aria-pressed="false"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-1 text-neutral-500 transition-colors duration-150 hover:text-neutral-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            @once
                <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
                document.addEventListener('click', function (event) {
                    const btn = event.target.closest('[data-action="toggle-password"]');
                    if (!btn) return;

                    const input = btn.previousElementSibling;
                    const svg = btn.querySelector('svg');
                    const showing = input.type === 'password';

                    input.type = showing ? 'text' : 'password';
                    btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
                    btn.setAttribute('aria-pressed', showing ? 'true' : 'false');
                    svg.innerHTML = showing
                        ? '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'
                        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
                });
                </script>
            @endonce
        @endif
    </div>

    @if($hasError)
        <p id="{{ $inputId }}-error" class="mt-1.5 text-[13px] text-error">{{ $error }}</p>
    @elseif($hint)
        <p class="mt-1.5 text-[13px] text-neutral-500">{{ $hint }}</p>
    @endif
</div>

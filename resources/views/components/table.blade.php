@props([])

{{--
    Wrapper only — pass real <thead>/<tbody> markup as the slot, since
    column structure varies too much per page to componentize further.
    Suggested cell classes for consumers:
      th: "px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wide text-neutral-500 bg-neutral-50"
      td: "px-4 py-3 text-[15px] text-neutral-700 border-t border-neutral-200"
--}}
<div {{ $attributes->merge([
    'class' => 'overflow-x-auto rounded-lg border border-neutral-200 '
        . 'max-[639px]:[box-shadow:inset_-16px_0_12px_-12px_rgba(16,24,40,0.14)]',
]) }}>
    <table class="w-full border-collapse text-[15px]">
        {{ $slot }}
    </table>
</div>

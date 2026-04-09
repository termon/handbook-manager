@props([
    'handbook',
    'activePanel',
    'panels',
    'selectedPageId' => null,
])

<nav class="flex flex-wrap gap-3">
    @foreach ($panels as $panelKey => $panelLabel)
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="$set('panel', '{{ $panelKey }}')"
                aria-current="{{ $activePanel === $panelKey ? 'page' : 'false' }}"
                class="{{ $activePanel === $panelKey ? 'border-zinc-950 bg-zinc-950 text-white dark:border-white dark:bg-white dark:text-zinc-950' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-300 hover:bg-white dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:border-zinc-600' }} rounded-full border px-4 py-2 text-sm font-medium transition"
            >
                {{ $panelLabel }}
            </button>

            @if ($activePanel === $panelKey)
                <span class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-medium uppercase tracking-[0.2em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    Active
                </span>
            @endif
        </div>
    @endforeach
</nav>

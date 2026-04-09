@props([
    'pages',
    'selectedPageId',
])

<div class="rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-2 pb-4 dark:border-zinc-700">
        <div>
            <x-ui::heading level="5">Pages</x-ui::heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $pages->count() }} total</p>
        </div>

        <x-ui::button wire:click="createPage" variant="dark" type="button">Add page</x-ui::button>
    </div>

    <div class="mx-2 mt-4 rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
        Drag pages by the handle to reorder them. The list order is saved automatically when you drop a page.
    </div>

    <div wire:sort="sortPages" class="mt-4 space-y-3">
        @foreach ($pages as $page)
            <div
                wire:key="editor-page-{{ $page->id }}"
                wire:sort:item="{{ $page->id }}"
                class="{{ $selectedPageId === $page->id ? 'border-zinc-950 bg-zinc-950 text-white dark:border-white dark:bg-gray-300 dark:text-zinc-950' : 'border-zinc-200 bg-zinc-50 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100' }} rounded-2xl border p-4 transition"
            >
                <div class="flex items-start gap-3">
                    <button type="button" wire:click="selectPage({{ $page->id }})" class="flex-1 text-left">
                        <p class="text-xs font-medium uppercase tracking-[0.25em] opacity-70">Page {{ $loop->iteration }}</p>
                        <p class="mt-2 text-sm font-semibold">{{ $page->title }}</p>
                    </button>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:sort:handle
                            class="inline-flex cursor-grab items-center gap-2 rounded-full border border-current/15 px-3 py-2 text-xs font-medium uppercase tracking-[0.25em] opacity-80 transition hover:opacity-100 active:cursor-grabbing"
                            title="Drag to reorder page"
                        >
                            <x-ui::svg icon="bars" size="sm" />
                            <span>Drag</span>
                        </button>

                        <div wire:sort:ignore>
                            <button type="button" wire:click="deletePage({{ $page->id }})" class="rounded-full border border-current/15 p-2 opacity-70 transition hover:opacity-100">
                                <x-ui::svg icon="trash" size="sm" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @error('selectedPageId')
        <p class="mt-4 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

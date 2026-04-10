@props([
    'selectedPageId' => null,
    'selectedPageIsEditable' => true,
    'selectedPageSourceHandbookTitle' => null,
    'pageBody' => '',
])

<div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-3 border-b border-zinc-200 pb-6 dark:border-zinc-700 md:flex-row md:items-end md:justify-between">
        <div>
            <x-ui::heading level="4">Markdown editor</x-ui::heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                @if ($selectedPageIsEditable)
                    Edit the selected page content and save when you are ready.
                @else
                    This page is shared from {{ $selectedPageSourceHandbookTitle }} and can't be edited here.
                @endif
            </p>
        </div>

        @if ($selectedPageIsEditable)
            <x-ui::button wire:click="savePage" variant="dark" type="button">Save page</x-ui::button>
        @else
            <span class="rounded-full border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
                Read only
            </span>
        @endif
    </div>

    <div class="mt-6 space-y-5">
        <div class="space-y-2">
            <x-ui::form.label for="pageTitle">Page title</x-ui::form.label>
            <x-ui::form.input wire:model.live="pageTitle" name="pageTitle" :disabled="! $selectedPageIsEditable" required />
            <x-ui::form.error for="pageTitle" />
        </div>

        <div class="space-y-2">
            <x-ui::form.label for="pageIsShareable">Shareable page</x-ui::form.label>
            <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
                <input type="checkbox" wire:model.live="pageIsShareable" @disabled(! $selectedPageIsEditable) class="rounded border-zinc-300 text-zinc-950 focus:ring-zinc-400 dark:border-zinc-600 dark:bg-zinc-900">
                <span>Allow this page to be reused in other handbooks.</span>
            </label>
            <x-ui::form.error for="pageIsShareable" />
        </div>

        @if ($selectedPageIsEditable)
            <livewire:markdown-editor
                :key="'markdown-editor-'.$selectedPageId"
                wire:model.live.debounce.300ms="pageBody"
                name="pageBody"
                label="Markdown"
                :rows="24"
                required
            />
        @else
            <div class="space-y-2">
                <x-ui::form.label for="pageBodyReadonly">Markdown</x-ui::form.label>
                <pre id="pageBodyReadonly" class="overflow-x-auto rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">{{ $pageBody }}</pre>
            </div>
        @endif
    </div>
</div>

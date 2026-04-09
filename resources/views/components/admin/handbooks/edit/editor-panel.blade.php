@props([
    'selectedPageId' => null,
])

<div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-3 border-b border-zinc-200 pb-6 dark:border-zinc-700 md:flex-row md:items-end md:justify-between">
        <div>
            <x-ui::heading level="4">Markdown editor</x-ui::heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Edit the selected page content and save when you are ready.</p>
        </div>

        <x-ui::button wire:click="savePage" variant="dark" type="button">Save page</x-ui::button>
    </div>

    <div class="mt-6 space-y-5">
        <div class="space-y-2">
            <x-ui::form.label for="pageTitle">Page title</x-ui::form.label>
            <x-ui::form.input wire:model.live="pageTitle" name="pageTitle" required />
            <x-ui::form.error for="pageTitle" />
        </div>

        <livewire:markdown-editor
            :key="'markdown-editor-'.$selectedPageId"
            wire:model.live.debounce.300ms="pageBody"
            name="pageBody"
            label="Markdown"
            :rows="24"
            required
        />
    </div>
</div>

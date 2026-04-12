@props([
    'handbook',
    'selectedPositionId',
    'pageTitle',
    'previewHtml',
])

<x-panel class="p-6">
    <div class="flex flex-col gap-2 border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <x-ui::heading level="4">Page preview</x-ui::heading>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">Preview the currently selected page rendered from markdown.</p>
    </div>

    @if ($selectedPositionId)
        <x-panel class="mt-6 border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-950">
            <p class="text-sm font-medium uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">{{ $handbook->title }}</p>
            <h2 class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $pageTitle }}</h2>
            <div class="prose prose-zinc mt-6 max-w-none prose-headings:tracking-tight prose-a:font-medium prose-a:text-zinc-950 prose-a:decoration-zinc-300 prose-a:underline-offset-4 prose-code:rounded prose-code:bg-zinc-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:text-[0.9em] prose-pre:rounded-2xl prose-pre:bg-zinc-950 prose-pre:text-zinc-100 dark:prose-invert dark:prose-a:text-zinc-50 dark:prose-a:decoration-zinc-600 dark:prose-code:bg-zinc-800">{!! $previewHtml !!}</div>
        </x-panel>
    @else
        <x-panel.empty class="mt-6">
            Select a page to preview it.
        </x-panel.empty>
    @endif
</x-panel>

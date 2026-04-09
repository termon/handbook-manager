<?php

use Livewire\Attributes\Modelable;
use Livewire\Component;

new class extends Component
{
    public ?string $id = null;

    public string $label = 'Markdown';

    #[Modelable]
    public string $value = '';

    public ?string $name = null;

    public int $rows = 24;

    public bool $required = false;
};
?>

@php
    $fieldId = $id ?? $name ?? 'markdown-editor';
@endphp

<div class="space-y-2">
    <label
        for="{{ $fieldId }}"
        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
    >
        {{ $label }}
    </label>

    <div wire:ignore>
        <textarea
            id="{{ $fieldId }}"
            rows="{{ $rows }}"
            @if ($name)
                name="{{ $name }}"
            @endif
            @if ($required)
                required
            @endif
            class="block w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-950 shadow-sm transition outline-none placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-50 dark:placeholder:text-zinc-500 dark:focus:border-blue-400 dark:focus:ring-blue-400/20"
        >{{ $value }}</textarea>
    </div>

    @error($name ?? 'value')
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

@script
    <script>
        let valueWatcherRegistered = false;

        const initializeEditor = () => {
            const textarea = $wire.$el.querySelector('#{{ $fieldId }}');

            if (! textarea || typeof window.EasyMDE === 'undefined') {
                window.setTimeout(initializeEditor, 50);

                return;
            }

            let editor = textarea._markdownEditorInstance;

            if (! editor) {
                editor = new window.EasyMDE({
                    element: textarea,
                    autoDownloadFontAwesome: false,
                    forceSync: true,
                    spellChecker: false,
                    status: false,
                    toolbar: [
                        'bold', 'italic', 'heading', '|',
                        'quote', 'unordered-list', 'ordered-list', '|',
                        'link', 'image',
                    ],
                });

                textarea._markdownEditorInstance = editor;

                editor.codemirror.on('change', () => {
                    const nextValue = editor.value();

                    if ($wire.get('value') !== nextValue) {
                        $wire.$set('value', nextValue, true);
                    }
                });
            }

            if (! valueWatcherRegistered) {
                $wire.$watch('value', (nextValue) => {
                    const normalizedValue = nextValue ?? '';

                    if (editor.value() !== normalizedValue) {
                        editor.value(normalizedValue);
                    }
                });

                valueWatcherRegistered = true;
            }
        };

        initializeEditor();
    </script>
@endscript

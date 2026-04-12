@props([
    'images',
    'hasImageUpload' => false,
    'pendingOverwriteImageId',
    'pendingOverwriteImageName',
])

<x-panel class="p-6">
    <div class="flex flex-col gap-2 border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <x-ui::heading level="4">Handbook images</x-ui::heading>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">Upload images for this handbook, then copy the markdown snippet and paste it where you want it in the page editor.</p>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,24rem)_minmax(0,1fr)]">
        <div class="space-y-4">
            <form
                x-data="{
                    selectedFile: null,
                    uploading: false,
                    progress: 0,
                    hasSelectedFile() {
                        return this.selectedFile !== null
                    },
                    syncSelectedFile(event) {
                        this.selectedFile = event.target.files?.[0] ?? null
                    },
                    clearSelectedFile() {
                        this.selectedFile = null
                        this.progress = 0

                        if (this.$refs.singleImageUpload) {
                            this.$refs.singleImageUpload.value = ''
                        }
                    },
                    async uploadSelectedFile() {
                        if (this.uploading || ! this.hasSelectedFile()) {
                            return
                        }

                        this.uploading = true
                        this.progress = 0

                        try {
                            await new Promise((resolve, reject) => {
                                $wire.upload(
                                    'imageUpload',
                                    this.selectedFile,
                                    () => resolve(),
                                    () => reject(),
                                    (event) => {
                                        this.progress = event.detail.progress
                                    },
                                )
                            })

                            await $wire.$call('uploadImage')
                        } finally {
                            this.uploading = false
                        }
                    }
                }"
                x-on:submit.prevent="uploadSelectedFile"
                x-on:single-image-upload-cleared.window="clearSelectedFile()"
                class="space-y-4"
            >
                <x-panel.inset class="space-y-4 rounded-3xl p-5">
                    <x-ui::form.input-group
                        x-ref="singleImageUpload"
                        x-on:change="syncSelectedFile($event)"
                        id="image-upload"
                        name="singleImageUpload"
                        type="file"
                        label="Single image upload"
                        accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml"
                    />

                    <x-ui::form.input-group wire:model="imageAltText" name="imageAltText" label="Alt text" placeholder="Describe the image" />

                    <p class="text-sm text-zinc-600 dark:text-zinc-300">Use the single-file uploader when you want overwrite confirmation or a custom alt text.</p>

                    <div x-cloak x-show="uploading" class="space-y-2">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Uploading selected file...
                        </p>
                        <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-zinc-900 transition-all dark:bg-zinc-100" x-bind:style="`width: ${progress}%`"></div>
                        </div>
                    </div>

                    <x-ui::button
                        type="submit"
                        variant="dark"
                        class="w-full justify-center"
                        x-bind:disabled="uploading || ! hasSelectedFile()"
                        x-bind:aria-busy="uploading"
                    >
                        Upload image
                    </x-ui::button>
                </x-panel.inset>
            </form>

            <form
                x-data="{
                    files: [],
                    selectedFileCount: 0,
                    uploading: false,
                    progress: 0,
                    hasSelectedFiles() {
                        return this.selectedFileCount > 0
                    },
                    selectedFiles() {
                        return this.files
                    },
                    syncSelectedFiles(event) {
                        this.files = Array.from(event.target.files ?? [])
                        this.selectedFileCount = this.files.length
                    },
                    async uploadBatches() {
                        if (this.uploading) {
                            return
                        }

                        if (! this.hasSelectedFiles()) {
                            return
                        }

                        this.uploading = true
                        this.progress = 0

                        try {
                            await $wire.$call('prepareMultiImageUpload')

                            const files = this.selectedFiles()
                            const batches = []
                            const batchSize = 3

                            for (let index = 0; index < files.length; index += batchSize) {
                                batches.push(files.slice(index, index + batchSize))
                            }

                            for (const [index, batch] of batches.entries()) {
                                await new Promise((resolve, reject) => {
                                    $wire.uploadMultiple(
                                        'imageUploads',
                                        batch,
                                        () => {
                                            this.progress = Math.round(((index + 1) / batches.length) * 100)
                                            resolve()
                                        },
                                        () => reject(),
                                        (event) => {
                                            this.progress = Math.round(((index + (event.detail.progress / 100)) / batches.length) * 100)
                                        },
                                        () => reject()
                                    )
                                })
                            }

                            await $wire.$call('uploadImages')
                            this.files = []
                            this.selectedFileCount = 0
                            this.progress = 0
                            this.$refs.multiImageUpload.value = ''
                        } finally {
                            this.uploading = false
                        }
                    }
                }"
                x-on:submit.prevent="uploadBatches"
                class="space-y-4"
            >
                <x-panel.inset class="space-y-4 rounded-3xl p-5">
                    <div class="space-y-2">
                        <x-ui::form.input-group
                            x-ref="multiImageUpload"
                            x-on:change="syncSelectedFiles($event)"
                            id="image-uploads"
                            name="multiImageUploads"
                            type="file"
                            label="Multiple image upload"
                            accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml"
                            multiple
                        />
                        @error('imageUploads')
                            <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @error('imageUploads.*')
                            <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <p class="text-sm text-zinc-600 dark:text-zinc-300">Batch uploads overwrite same-name files automatically, upload in small groups, and default alt text to each filename without its extension.</p>

                    <div x-cloak x-show="uploading" class="space-y-2">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Uploading <span x-text="selectedFiles().length"></span> selected files in batches...
                        </p>
                        <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-zinc-900 transition-all dark:bg-zinc-100" x-bind:style="`width: ${progress}%`"></div>
                        </div>
                    </div>

                    <x-ui::button
                        type="submit"
                        variant="dark"
                        class="w-full"
                        x-bind:disabled="uploading || ! hasSelectedFiles()"
                        x-bind:aria-busy="uploading"
                    >
                        Upload images
                    </x-ui::button>
                </x-panel.inset>
            </form>
        </div>

        <div class="space-y-3">
            @if ($pendingOverwriteImageId)
                <div class="rounded-3xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    <p class="font-medium">A handbook image named "{{ $pendingOverwriteImageName }}" already exists.</p>
                    <p class="mt-2">Overwrite the existing file with the newly uploaded image?</p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-ui::button wire:click="confirmImageOverwrite" variant="dark" type="button">Overwrite file</x-ui::button>
                        <x-ui::button wire:click="cancelImageOverwrite" variant="light" type="button">Cancel</x-ui::button>
                    </div>
                </div>
            @endif

            @forelse ($images as $image)
                <div wire:key="handbook-image-{{ $image->id }}" class="flex flex-col gap-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <img src="{{ $image->relativeUrl() }}" alt="{{ $image->alt_text ?? $image->name }}" class="size-16 rounded-2xl object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" />

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-zinc-950 dark:text-zinc-50">{{ $image->name }}</p>
                                <x-ui::badge variant="yellow" class="text-xs rounded-full">
                                    {{ str_starts_with((string) $image->path, 'data:image/') ? 'B' : 'F' }}
                                </x-ui::badge>                                  
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $image->alt_text }}</p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Markdown path: <code>{{ $image->name }}</code></p>
                            <p class="mt-1 text-xs uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">{{ number_format($image->size / 1024, 1) }} KB</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3" x-data="{ copied: false }">
                        <x-ui::button
                            title="Copy markdown to clipboard"
                            type="button"
                            data-markdown-snippet="{{ $image->markdownSnippet() }}"
                            x-on:click="navigator.clipboard.writeText($el.dataset.markdownSnippet); copied = true; setTimeout(() => copied = false, 1500)"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-1.5 font-medium text-gray-900 transition-colors hover:bg-gray-200 hover:text-black focus:outline-none focus:ring-1 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-100 dark:hover:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                        >
                            <x-ui::svg icon="document-duplicate" class="shrink-0" />
                            <span x-show="! copied"></span>
                            <span x-show="copied" x-cloak>Copied</span>
                        </x-ui::button>

                        <x-ui::button 
                            title="Delete image"
                            wire:click="deleteImage({{ $image->id }})" 
                            variant="ored" 
                            icon="trash" 
                            type="button"></x-ui::button>
                    </div>
                </div>
            @empty
                <x-panel.empty>
                    No images uploaded for this handbook yet.
                </x-panel.empty>
            @endforelse
        </div>
    </div>
</x-panel>

@props([
    'images',
    'pendingOverwriteImageId',
    'pendingOverwriteImageName',
])

<div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-2 border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <x-ui::heading level="4">Handbook images</x-ui::heading>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">Upload images for this handbook, then copy the markdown snippet and paste it where you want it in the page editor.</p>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,24rem)_minmax(0,1fr)]">
        <div class="space-y-4">
            <form wire:submit="uploadImage" class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-950">
                <div class="space-y-2">
                    <label for="image-upload" class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Single image upload</label>
                    <input
                        id="image-upload"
                        type="file"
                        wire:model="imageUpload"
                        accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml"
                        class="block w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 file:me-3 file:rounded-full file:border-0 file:bg-zinc-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:file:bg-white dark:file:text-zinc-900"
                    />
                    @error('imageUpload')
                        <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <x-ui::form.label for="imageAltText">Alt text</x-ui::form.label>
                    <x-ui::form.input wire:model="imageAltText" name="imageAltText" placeholder="Describe the image" />
                    <x-ui::form.error for="imageAltText" />
                </div>

                <p class="text-sm text-zinc-600 dark:text-zinc-300">Use the single-file uploader when you want overwrite confirmation or a custom alt text.</p>

                <x-ui::button type="submit" variant="dark" class="w-full justify-center">Upload image</x-ui::button>
            </form>

            <form
                x-data="{
                    files: [],
                    uploading: false,
                    progress: 0,
                    async uploadBatches() {
                        if (this.uploading) {
                            return
                        }

                        if (this.files.length === 0) {
                            await $wire.$call('uploadImages')

                            return
                        }

                        this.uploading = true
                        this.progress = 0

                        try {
                            await $wire.$call('prepareMultiImageUpload')

                            const batches = []
                            const batchSize = 3

                            for (let index = 0; index < this.files.length; index += batchSize) {
                                batches.push(this.files.slice(index, index + batchSize))
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
                            this.progress = 0
                            this.$refs.multiImageUpload.value = ''
                        } finally {
                            this.uploading = false
                        }
                    }
                }"
                x-on:submit.prevent="uploadBatches"
                class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-950"
            >
                <div class="space-y-2">
                    <label for="image-uploads" class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Multiple image upload</label>
                    <input
                        x-ref="multiImageUpload"
                        id="image-uploads"
                        type="file"
                        multiple
                        x-on:change="files = Array.from($event.target.files)"
                        accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml"
                        class="block w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 file:me-3 file:rounded-full file:border-0 file:bg-zinc-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:file:bg-white dark:file:text-zinc-900"
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
                        Uploading <span x-text="files.length"></span> selected files in batches...
                    </p>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                        <div class="h-full rounded-full bg-zinc-900 transition-all dark:bg-zinc-100" x-bind:style="`width: ${progress}%`"></div>
                    </div>
                </div>

                <x-ui::button
                    type="submit"
                    variant="dark"
                    class="w-full"
                    x-bind:disabled="uploading"
                    x-bind:aria-busy="uploading"
                >
                    Upload images
                </x-ui::button>
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
                            <p class="text-sm font-semibold text-zinc-950 dark:text-zinc-50">{{ $image->name }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $image->alt_text }}</p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Markdown path: <code>{{ $image->name }}</code></p>
                            <p class="mt-1 text-xs uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">{{ number_format($image->size / 1024, 1) }} KB</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3" x-data="{ copied: false }">
                        <button
                            type="button"
                            x-on:click="navigator.clipboard.writeText(@js($image->markdownSnippet())); copied = true; setTimeout(() => copied = false, 1500)"
                            class="inline-flex items-center rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-900 transition hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-zinc-600"
                        >
                            <span x-show="! copied">Copy</span>
                            <span x-show="copied" x-cloak>Copied</span>
                        </button>

                        <x-ui::button wire:click="deleteImage({{ $image->id }})" variant="red" type="button">Delete</x-ui::button>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
                    No images uploaded for this handbook yet.
                </div>
            @endforelse
        </div>
    </div>
</div>

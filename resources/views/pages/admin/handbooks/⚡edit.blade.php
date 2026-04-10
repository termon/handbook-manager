<?php

use App\Models\Handbook;
use App\Models\HandbookImage;
use App\Models\HandbookPage;
use App\Models\HandbookPagePosition;
use App\Models\User;
use App\Support\HandbookMarkdownRenderer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Edit handbook')] class extends Component {
    use AuthorizesRequests;
    use WithFileUploads;

    public Handbook $handbook;

    public string $handbookTitle = '';

    public string $handbookDescription = '';

    public string $ownerId = '';

    public bool $isListed = true;

    #[Url(as: 'page')]
    public ?int $selectedPositionId = null;

    public string $pageTitle = '';

    public string $pageBody = '';

    public bool $pageIsShareable = false;

    public bool $showSharedPagePicker = false;

    public string $sharedPageSearch = '';

    public string $selectedSharedPageId = '';

    public ?TemporaryUploadedFile $imageUpload = null;

    /**
     * @var array<int, TemporaryUploadedFile>
     */
    public $imageUploads = [];

    public string $imageAltText = '';

    public ?int $pendingOverwriteImageId = null;

    public string $pendingOverwriteImageName = '';

    #[Url(as: 'panel')]
    public string $panel = 'editor';

    public function mount(Handbook $handbook): void
    {
        $this->handbook = $handbook;
        $this->authorize('update', $this->handbook);
        $this->panel = $this->normalizePanel($this->panel);
        $this->fillHandbookForm();

        $positionId = $this->selectedPositionId
            ?? (int) $this->handbook->positions()->orderBy('position')->value('id');

        $this->selectPosition($positionId);
    }

    public function saveHandbook(): void
    {
        $this->authorize('update', $this->handbook);

        $validated = $this->validate([
            'handbookTitle' => ['required', 'string', 'max:255'],
            'handbookDescription' => ['nullable', 'string', 'max:2000'],
            'ownerId' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'author')],
            'isListed' => ['boolean'],
        ]);

        $this->handbook->update([
            'user_id' => $this->resolveOwnerId($validated['ownerId'] ?? null),
            'title' => $validated['handbookTitle'],
            'slug' => $this->uniqueHandbookSlug($validated['handbookTitle'], $this->handbook),
            'description' => blank($validated['handbookDescription']) ? null : $validated['handbookDescription'],
            'is_listed' => (bool) ($validated['isListed'] ?? true),
        ]);

        $this->fillHandbookForm();
    }

    public function createPage(): void
    {
        $this->authorize('update', $this->handbook);

        $page = $this->handbook->pages()->create([
            'title' => 'New Page',
            'slug' => $this->uniquePageSlug('New Page'),
            'position' => $this->nextPosition(),
            'body' => "# New Page\n\nAdd markdown content here.",
        ]);

        $positionId = (int) $page->positions()
            ->where('handbook_id', $this->handbook->id)
            ->value('id');

        $this->selectPosition($positionId);
    }

    public function beginAddSharedPage(): void
    {
        $this->authorize('update', $this->handbook);

        $this->showSharedPagePicker = true;
        $this->sharedPageSearch = '';
        $this->selectedSharedPageId = '';
        $this->resetValidation(['sharedPageSearch', 'selectedSharedPageId']);
    }

    public function cancelAddSharedPage(): void
    {
        $this->reset('showSharedPagePicker', 'sharedPageSearch', 'selectedSharedPageId');
        $this->resetValidation(['sharedPageSearch', 'selectedSharedPageId']);
    }

    public function attachSharedPage(): void
    {
        $this->authorize('update', $this->handbook);

        $validated = $this->validate([
            'selectedSharedPageId' => ['required', 'integer', 'exists:handbook_pages,id'],
        ]);

        $page = HandbookPage::query()
            ->with('handbook')
            ->findOrFail((int) $validated['selectedSharedPageId']);

        $this->authorize('attachSharedPage', [$this->handbook, $page]);

        $position = HandbookPagePosition::query()->firstOrCreate(
            [
                'handbook_id' => $this->handbook->id,
                'handbook_page_id' => $page->id,
            ],
            [
                'position' => $this->nextPosition(),
            ],
        );

        $this->cancelAddSharedPage();
        $this->selectPosition($position->id);
    }

    public function selectPosition(int $positionId): void
    {
        $this->authorize('view', $this->handbook);

        $position = $this->handbook->positions()
            ->with('page.handbook')
            ->findOrFail($positionId);

        $page = $position->page;

        $this->selectedPositionId = $position->id;
        $this->pageTitle = $page->title;
        $this->pageBody = $page->body;
        $this->pageIsShareable = $page->is_shareable;
    }

    public function savePage(): void
    {
        $this->authorize('update', $this->handbook);

        $page = $this->selectedPage();

        if (! $page->isEditableIn($this->handbook)) {
            $this->addError('selectedPositionId', 'Shared pages can only be edited in their source handbook.');

            return;
        }

        $validated = $this->validate([
            'pageTitle' => ['required', 'string', 'max:255'],
            'pageBody' => ['required', 'string'],
            'pageIsShareable' => ['boolean'],
        ]);

        $page->update([
            'title' => $validated['pageTitle'],
            'slug' => $this->uniquePageSlug($validated['pageTitle'], $page),
            'body' => $validated['pageBody'],
            'is_shareable' => (bool) ($validated['pageIsShareable'] ?? false),
        ]);

        $this->selectPosition($this->selectedPositionId);
    }

    public function uploadImage(): void
    {
        $this->authorize('update', $this->handbook);

        $validated = $this->validate([
            'imageUpload' => ['required', 'image', 'max:5120'],
            'imageAltText' => ['nullable', 'string', 'max:255'],
        ]);

        $existingImage = $this->existingImageByUpload($this->imageUpload);

        if ($existingImage !== null) {
            $this->pendingOverwriteImageId = $existingImage->id;
            $this->pendingOverwriteImageName = $existingImage->name;

            return;
        }

        $this->storeUploadedImage(altText: $this->imageAltText);
        $this->resetImageUploadState();
    }

    public function uploadImages(): void
    {
        $this->authorize('update', $this->handbook);

        $validated = $this->validate([
            'imageUploads' => ['required', 'array', 'min:1'],
            'imageUploads.*' => ['image', 'max:5120'],
        ]);

        foreach ($validated['imageUploads'] as $uploadedImage) {
            $existingImage = $this->existingImageByUpload($uploadedImage);

            $this->storeUploadedImage(
                upload: $uploadedImage,
                existingImage: $existingImage,
                altText: pathinfo($this->originalUploadName($uploadedImage), PATHINFO_FILENAME),
            );
        }

        $this->cancelImageOverwrite();
        $this->resetMultiImageUploadState();
    }

    public function prepareMultiImageUpload(): void
    {
        $this->authorize('update', $this->handbook);

        $this->resetMultiImageUploadState();
        $this->resetValidation('imageUploads');
        $this->cancelImageOverwrite();
    }

    public function updatedImageUploads(): void
    {
        $this->resetValidation('imageUploads');
    }

    public function confirmImageOverwrite(): void
    {
        $this->authorize('update', $this->handbook);

        $existingImage = $this->handbook->images()->findOrFail($this->pendingOverwriteImageId);
        $this->storeUploadedImage(existingImage: $existingImage, altText: $this->imageAltText);
        $this->resetImageUploadState();
    }

    public function cancelImageOverwrite(): void
    {
        $this->reset('pendingOverwriteImageId', 'pendingOverwriteImageName');
    }

    public function deleteImage(int $imageId): void
    {
        $this->authorize('update', $this->handbook);

        $image = $this->handbook->images()->findOrFail($imageId);
        $image->delete();
    }

    public function removePosition(int $positionId): void
    {
        $this->authorize('update', $this->handbook);

        if ($this->handbook->positions()->count() === 1) {
            $this->addError('selectedPositionId', 'A handbook must keep at least one page.');

            return;
        }

        $position = $this->handbook->positions()
            ->with('page')
            ->findOrFail($positionId);

        $page = $position->page;

        if ($page->isEditableIn($this->handbook)) {
            if ($page->isShared()) {
                $this->addError('selectedPositionId', 'This page is shared with other handbooks and cannot be deleted here.');

                return;
            }

            $page->delete();
        } else {
            $position->delete();
        }

        $this->resequencePositions();
        $nextPositionId = (int) $this->handbook->positions()->orderBy('position')->value('id');

        $this->resetErrorBag('selectedPositionId');
        $this->selectPosition($nextPositionId);
    }

    public function sortPositions($positionId, $position): void
    {
        $this->authorize('update', $this->handbook);

        $orderedIds = $this->positions->pluck('id')->reject(fn ($id) => (int) $id === (int) $positionId)->values();
        $orderedIds->splice((int) $position, 0, [(int) $positionId]);

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds->values() as $index => $id) {
                $this->handbook->positions()->whereKey($id)->update(['position' => $index]);
            }
        });

        if ($this->selectedPositionId !== null) {
            $this->selectPosition($this->selectedPositionId);
        }
    }

    #[Computed]
    public function positions()
    {
        return $this->handbook->positions()
            ->with('page.handbook')
            ->orderBy('position')
            ->get();
    }

    #[Computed]
    public function previewHtml(): HtmlString
    {
        $previewPage = new HandbookPage([
            'handbook_id' => $this->handbook->id,
            'title' => $this->pageTitle,
            'slug' => Str::slug($this->pageTitle),
            'position' => 0,
            'body' => $this->pageBody,
            'is_shareable' => $this->pageIsShareable,
        ]);

        $sourceHandbook = $this->selectedPage()?->handbook?->loadMissing('images') ?? $this->handbook->loadMissing('images');
        $previewPage->setRelation('handbook', $sourceHandbook);

        return new HtmlString(app(HandbookMarkdownRenderer::class)->render($this->handbook, $previewPage));
    }

    #[Computed]
    public function shareablePages()
    {
        $search = trim($this->sharedPageSearch);

        return HandbookPage::query()
            ->with('handbook')
            ->where('is_shareable', true)
            ->when(! Auth::user()->isAdmin(), fn ($query) => $query->whereHas('handbook', fn ($handbookQuery) => $handbookQuery->where('user_id', Auth::id())))
            ->whereDoesntHave('positions', fn ($query) => $query->where('handbook_id', $this->handbook->id))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchTerm = "%{$search}%";

                    $searchQuery
                        ->where('title', 'like', $searchTerm)
                        ->orWhereHas('handbook', fn ($handbookQuery) => $handbookQuery->where('title', 'like', $searchTerm));
                });
            })
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function selectedPageIsEditable(): bool
    {
        return $this->selectedPage()?->isEditableIn($this->handbook) ?? false;
    }

    #[Computed]
    public function owners()
    {
        return User::query()
            ->where('role', 'author')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function images()
    {
        return $this->handbook->images()->get();
    }

    #[Computed]
    public function canAssignOwner(): bool
    {
        return Auth::user()->can('assignOwner', Handbook::class);
    }

    #[Computed]
    public function availablePanels(): array
    {
        return [
            'editor' => 'Markdown editor',
            'preview' => 'Page preview',
            'details' => 'Handbook details',
            'images' => 'Images',
        ];
    }

    private function selectedPosition(): ?HandbookPagePosition
    {
        if ($this->selectedPositionId === null) {
            return null;
        }

        return $this->handbook->positions()
            ->with('page.handbook.images')
            ->findOrFail($this->selectedPositionId);
    }

    private function selectedPage(): ?HandbookPage
    {
        return $this->selectedPosition()?->page;
    }

    private function fillHandbookForm(): void
    {
        $this->handbook->refresh()->loadMissing('owner');
        $this->handbookTitle = $this->handbook->title;
        $this->handbookDescription = $this->handbook->description ?? '';
        $this->ownerId = (string) ($this->handbook->user_id ?? '');
        $this->isListed = $this->handbook->is_listed;
    }

    private function nextPosition(): int
    {
        return (int) $this->handbook->positions()->max('position') + 1;
    }

    private function resequencePositions(): void
    {
        DB::transaction(function (): void {
            foreach ($this->handbook->positions()->orderBy('position')->get()->values() as $index => $position) {
                $position->update(['position' => $index]);
            }
        });
    }

    private function uniqueHandbookSlug(string $title, ?Handbook $ignore = null): string
    {
        $slug = Str::slug($title);
        $candidate = $slug;
        $suffix = 1;

        while (Handbook::query()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function uniquePageSlug(string $title, ?HandbookPage $ignore = null): string
    {
        $slug = Str::slug($title);
        $candidate = $slug;
        $suffix = 1;

        while ($this->handbook->pages()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function resolveOwnerId(mixed $ownerId): int
    {
        if (Auth::user()->isAdmin()) {
            validator(
                ['owner_id' => $ownerId],
                ['owner_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'author')]],
            )->validate();

            return (int) $ownerId;
        }

        return Auth::id();
    }

    private function existingImageByUpload(?TemporaryUploadedFile $upload): ?HandbookImage
    {
        return $this->handbook->images()
            ->where('name', $this->sanitizedUploadName($upload))
            ->first();
    }

    private function originalUploadName(?TemporaryUploadedFile $upload): string
    {
        return Str::of($upload?->getClientOriginalName() ?? '')
            ->replace('\\', '/')
            ->afterLast('/')
            ->toString();
    }

    private function sanitizedUploadName(?TemporaryUploadedFile $upload): string
    {
        if ($upload === null) {
            return '';
        }

        return HandbookImage::sanitizedUploadName($upload);
    }

    private function storeUploadedImage(
        ?TemporaryUploadedFile $upload = null,
        ?HandbookImage $existingImage = null,
        ?string $altText = null,
    ): HandbookImage
    {
        $upload ??= $this->imageUpload;
        $originalName = $this->originalUploadName($upload);
        $storedName = $this->sanitizedUploadName($upload);

        $image = $existingImage ?? new HandbookImage();
        $image->handbook()->associate($this->handbook);
        $image->fill([
            'handbook_id' => $this->handbook->id,
            'disk' => 'public',
            'path' => $upload,
            'name' => $storedName,
            'alt_text' => blank($altText) ? pathinfo($originalName, PATHINFO_FILENAME) : $altText,
            'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
            'size' => $upload->getSize(),
        ]);
        $image->save();

        return $image;
    }

    private function resetImageUploadState(): void
    {
        $this->reset('imageUpload', 'imageAltText', 'pendingOverwriteImageId', 'pendingOverwriteImageName');
    }

    private function resetMultiImageUploadState(): void
    {
        $this->reset('imageUploads');
    }

    private function normalizePanel(string $panel): string
    {
        return array_key_exists($panel, $this->availablePanels())
            ? $panel
            : 'editor';
    }
}; ?>

@assets
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
    <script src="https://unpkg.com/easymde/dist/easymde.min.js" data-easymde-script></script>
@endassets

<section class="w-full space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.3em] text-zinc-500 dark:text-zinc-400">Manage / Handbook</p>
            <h1 class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $handbook->title }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <span>Current panel</span>
                <span class="rounded-full border border-zinc-200 bg-zinc-100 px-3 py-1 text-xs font-medium uppercase tracking-[0.25em] text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    {{ $this->availablePanels[$panel] }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <x-ui::link :href="route('handbooks.show', ['handbook' => $handbook])" icon="eye" variant="light">View</x-ui::link>
            <x-ui::link :href="route('admin.handbooks.index')" icon="arrow-left" wire:navigate variant="light">Handbooks</x-ui::link>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[21rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            <x-admin.handbooks.edit.page-list
                :handbook="$handbook"
                :positions="$this->positions"
                :selected-position-id="$selectedPositionId"
            />

            <div class="rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap gap-3">
                    <x-ui::button wire:click="createPage" variant="dark" type="button">Create page</x-ui::button>
                    <x-ui::button wire:click="beginAddSharedPage" variant="light" type="button">Add shared</x-ui::button>
                </div>

                @if ($showSharedPagePicker)
                    <div class="mt-4 space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        @if ($this->shareablePages->isNotEmpty())
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">
                                Shared pages stay linked to their source handbook. You can add or remove them here, but edits still happen in the original handbook.
                            </div>

                            <div class="space-y-2">
                                <x-ui::form.label for="sharedPageSearch">Search shareable pages</x-ui::form.label>
                                <x-ui::form.input
                                    wire:model.live.debounce.300ms="sharedPageSearch"
                                    name="sharedPageSearch"
                                    placeholder="Filter by page title or source handbook"
                                />
                            </div>

                            <p class="text-xs font-medium uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">
                                {{ $this->shareablePages->count() }} available
                            </p>

                            <div class="max-h-96 space-y-3 overflow-y-auto pr-1">
                                @foreach ($this->shareablePages as $page)
                                    <button
                                        type="button"
                                        wire:key="shareable-page-option-{{ $page->id }}"
                                        wire:click="$set('selectedSharedPageId', '{{ $page->id }}')"
                                        class="{{ $selectedSharedPageId === (string) $page->id ? 'border-zinc-950 bg-zinc-950 text-white dark:border-white dark:bg-zinc-100 dark:text-zinc-950' : 'border-zinc-200 bg-white text-zinc-900 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-zinc-600 dark:hover:bg-zinc-800' }} block w-full rounded-2xl border p-4 text-left transition"
                                    >
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="space-y-2">
                                                <p class="text-sm font-semibold">{{ $page->title }}</p>
                                                <p class="text-xs font-medium uppercase tracking-[0.25em] opacity-70">
                                                    Source handbook: {{ $page->handbook->title }}
                                                </p>
                                            </div>

                                            <span class="rounded-full border border-current/15 px-2 py-1 text-[11px] font-medium uppercase tracking-[0.2em] opacity-80">
                                                Shared
                                            </span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            <x-ui::form.error for="selectedSharedPageId" />

                            <div class="flex flex-wrap gap-3">
                                <x-ui::button wire:click="attachSharedPage" variant="dark" type="button">Add shared page</x-ui::button>
                                <x-ui::button wire:click="cancelAddSharedPage" variant="light" type="button">Cancel</x-ui::button>
                            </div>
                        @else
                            <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                                <p>
                                    {{ blank($sharedPageSearch) ? 'No shareable pages are available to add to this handbook.' : 'No shareable pages match your search.' }}
                                </p>
                                <x-ui::button wire:click="cancelAddSharedPage" variant="light" type="button">Close</x-ui::button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </aside>

        <div class="space-y-6">
            <div class="rounded-3xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <x-admin.handbooks.edit.panel-nav
                    :handbook="$handbook"
                    :active-panel="$panel"
                    :panels="$this->availablePanels"
                    :selected-page-id="$selectedPositionId"
                />
            </div>

            @if ($panel === 'details')
                <x-admin.handbooks.edit.details-panel
                    :handbook="$handbook"
                    :can-assign-owner="$this->canAssignOwner"
                    :owners="$this->owners"
                    :owner-id="$ownerId"
                    :is-listed="$isListed"
                />
            @elseif ($panel === 'preview')
                <x-admin.handbooks.edit.preview-panel
                    :handbook="$handbook"
                    :selected-position-id="$selectedPositionId"
                    :page-title="$pageTitle"
                    :preview-html="$this->previewHtml"
                />
            @elseif ($panel === 'images')
                <x-admin.handbooks.edit.images-panel
                    :images="$this->images"
                    :has-image-upload="$imageUpload !== null"
                    :pending-overwrite-image-id="$pendingOverwriteImageId"
                    :pending-overwrite-image-name="$pendingOverwriteImageName"
                />
            @else
                <x-admin.handbooks.edit.editor-panel
                    :selected-page-id="$selectedPositionId"
                    :selected-page-is-editable="$this->selectedPageIsEditable"
                    :selected-page-source-handbook-title="$this->selectedPage()?->handbook?->title"
                    :page-body="$pageBody"
                />
            @endif
        </div>
    </div>
</section>

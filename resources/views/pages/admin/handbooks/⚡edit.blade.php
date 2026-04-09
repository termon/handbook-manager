<?php

use App\Models\Handbook;
use App\Models\HandbookImage;
use App\Models\HandbookPage;
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

    #[Url(as: 'page')]
    public ?int $selectedPageId = null;

    public string $pageTitle = '';

    public string $pageBody = '';

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

        $pageId = $this->selectedPageId
            ?? (int) $this->handbook->pages()->orderBy('position')->value('id');

        $this->selectPage($pageId);
    }

    public function saveHandbook(): void
    {
        $this->authorize('update', $this->handbook);

        $validated = $this->validate([
            'handbookTitle' => ['required', 'string', 'max:255'],
            'handbookDescription' => ['nullable', 'string', 'max:2000'],
            'ownerId' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'author')],
        ]);

        $this->handbook->update([
            'user_id' => $this->resolveOwnerId($validated['ownerId'] ?? null),
            'title' => $validated['handbookTitle'],
            'slug' => $this->uniqueHandbookSlug($validated['handbookTitle'], $this->handbook),
            'description' => blank($validated['handbookDescription']) ? null : $validated['handbookDescription'],
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

        $this->selectPage($page->id);
    }

    public function selectPage(int $pageId): void
    {
        $this->authorize('view', $this->handbook);

        $page = $this->handbook->pages()->findOrFail($pageId);

        $this->selectedPageId = $page->id;
        $this->pageTitle = $page->title;
        $this->pageBody = $page->body;
    }

    public function savePage(): void
    {
        $this->authorize('update', $this->handbook);

        $page = $this->selectedPage();

        $validated = $this->validate([
            'pageTitle' => ['required', 'string', 'max:255'],
            'pageBody' => ['required', 'string'],
        ]);

        $page->update([
            'title' => $validated['pageTitle'],
            'slug' => $this->uniquePageSlug($validated['pageTitle'], $page),
            'body' => $validated['pageBody'],
        ]);

        $this->selectPage($page->id);
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

    public function deletePage(int $pageId): void
    {
        $this->authorize('update', $this->handbook);

        if ($this->handbook->pages()->count() === 1) {
            $this->addError('selectedPageId', 'A handbook must keep at least one page.');

            return;
        }

        $page = $this->handbook->pages()->findOrFail($pageId);
        $page->delete();

        $this->resequencePages();
        $nextPageId = (int) $this->handbook->pages()->orderBy('position')->value('id');

        $this->resetErrorBag('selectedPageId');
        $this->selectPage($nextPageId);
    }

    public function sortPages($pageId, $position): void
    {
        $this->authorize('update', $this->handbook);

        $orderedIds = $this->pages->pluck('id')->reject(fn ($id) => (int) $id === (int) $pageId)->values();
        $orderedIds->splice((int) $position, 0, [(int) $pageId]);

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds->values() as $index => $id) {
                $this->handbook->pages()->whereKey($id)->update(['position' => $index]);
            }
        });

        if ($this->selectedPageId !== null) {
            $this->selectPage($this->selectedPageId);
        }
    }

    #[Computed]
    public function pages()
    {
        return $this->handbook->pages()->orderBy('position')->get();
    }

    #[Computed]
    public function previewHtml(): HtmlString
    {
        return new HtmlString(app(HandbookMarkdownRenderer::class)->render($this->handbook, $this->pageBody));
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

    private function selectedPage(): HandbookPage
    {
        return $this->handbook->pages()->findOrFail($this->selectedPageId);
    }

    private function fillHandbookForm(): void
    {
        $this->handbook->refresh()->loadMissing('owner');
        $this->handbookTitle = $this->handbook->title;
        $this->handbookDescription = $this->handbook->description ?? '';
        $this->ownerId = (string) ($this->handbook->user_id ?? '');
    }

    private function nextPosition(): int
    {
        return (int) $this->handbook->pages()->max('position') + 1;
    }

    private function resequencePages(): void
    {
        DB::transaction(function (): void {
            foreach ($this->handbook->pages()->orderBy('position')->get()->values() as $index => $page) {
                $page->update(['position' => $index]);
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
                :pages="$this->pages"
                :selected-page-id="$selectedPageId"
            />
        </aside>

        <div class="space-y-6">
            <div class="rounded-3xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <x-admin.handbooks.edit.panel-nav
                    :handbook="$handbook"
                    :active-panel="$panel"
                    :panels="$this->availablePanels"
                    :selected-page-id="$selectedPageId"
                />
            </div>

            @if ($panel === 'details')
                <x-admin.handbooks.edit.details-panel
                    :handbook="$handbook"
                    :can-assign-owner="$this->canAssignOwner"
                    :owners="$this->owners"
                    :owner-id="$ownerId"
                />
            @elseif ($panel === 'preview')
                <x-admin.handbooks.edit.preview-panel
                    :handbook="$handbook"
                    :selected-page-id="$selectedPageId"
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
                <x-admin.handbooks.edit.editor-panel :selected-page-id="$selectedPageId" />
            @endif
        </div>
    </div>
</section>

<?php

use App\Models\Handbook;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Handbooks')] class extends Component {
    use AuthorizesRequests;

    public ?int $duplicateSourceHandbookId = null;

    public ?int $handbookPendingDeletionId = null;

    public string $handbookPendingDeletionTitle = '';

    public string $duplicateTitle = '';

    public string $duplicateOwnerId = '';

    public bool $showDeleteHandbookModal = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Handbook::class);
    }

    public function confirmDeleteHandbook(int $handbookId): void
    {
        $handbook = Handbook::query()->findOrFail($handbookId);
        $this->authorize('delete', $handbook);

        $this->handbookPendingDeletionId = $handbook->id;
        $this->handbookPendingDeletionTitle = $handbook->title;
        $this->showDeleteHandbookModal = true;
    }

    public function cancelDeleteHandbook(): void
    {
        $this->reset(
            'handbookPendingDeletionId',
            'handbookPendingDeletionTitle',
            'showDeleteHandbookModal',
        );
    }

    public function deleteHandbook(): void
    {
        $handbook = Handbook::query()->findOrFail($this->handbookPendingDeletionId);
        $this->authorize('delete', $handbook);
        $handbook->delete();

        $this->cancelDeleteHandbook();
    }

    public function beginDuplicate(int $handbookId): void
    {
        $this->authorize('create', Handbook::class);

        $handbook = Handbook::query()
            ->with('owner:id')
            ->findOrFail($handbookId);

        $this->duplicateSourceHandbookId = $handbook->id;
        $this->duplicateTitle = "{$handbook->title} Copy";
        $this->duplicateOwnerId = (string) ($handbook->user_id ?? '');

        $this->resetValidation(['duplicateTitle', 'duplicateOwnerId']);
    }

    public function cancelDuplicate(): void
    {
        $this->reset('duplicateSourceHandbookId', 'duplicateTitle', 'duplicateOwnerId');
        $this->resetValidation(['duplicateTitle', 'duplicateOwnerId']);
    }

    public function duplicateHandbook(): void
    {
        $this->authorize('create', Handbook::class);

        $validated = $this->validate([
            'duplicateSourceHandbookId' => ['required', 'integer', 'exists:handbooks,id'],
            'duplicateTitle' => ['required', 'string', 'max:255'],
            'duplicateOwnerId' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'author')],
        ]);

        $sourceHandbook = Handbook::query()
            ->with([
                'pages' => fn ($query) => $query->orderBy('position'),
                'images',
            ])
            ->findOrFail($validated['duplicateSourceHandbookId']);

        $copiedHandbook = DB::transaction(function () use ($sourceHandbook, $validated): Handbook {
            $copiedHandbook = Handbook::create([
                'user_id' => (int) $validated['duplicateOwnerId'],
                'title' => $validated['duplicateTitle'],
                'slug' => $this->uniqueHandbookSlug($validated['duplicateTitle']),
                'description' => $sourceHandbook->description,
                'is_listed' => $sourceHandbook->is_listed,
            ]);

            foreach ($sourceHandbook->images as $image) {
                $copiedPath = "handbooks/{$copiedHandbook->id}/images/{$image->name}";

                if (Storage::disk($image->disk)->exists($image->path)) {
                    Storage::disk($image->disk)->copy($image->path, $copiedPath);
                }

                $copiedHandbook->images()->create([
                    'disk' => $image->disk,
                    'path' => $copiedPath,
                    'name' => $image->name,
                    'alt_text' => $image->alt_text,
                    'mime_type' => $image->mime_type,
                    'size' => $image->size,
                ]);
            }

            foreach ($sourceHandbook->pages as $page) {
                $copiedHandbook->pages()->create([
                    'title' => $page->title,
                    'slug' => $this->uniquePageSlug($copiedHandbook, $page->title),
                    'position' => $page->position,
                    'body' => $this->copiedPageBody($page->body, $sourceHandbook, $copiedHandbook),
                ]);
            }

            return $copiedHandbook;
        });

        $this->cancelDuplicate();

        $this->redirect(route('admin.handbooks.edit', $copiedHandbook, absolute: false), navigate: true);
    }

    #[Computed]
    public function handbooks()
    {
        $query = Handbook::query()
            ->with(['owner:id,name'])
            ->withCount('pages')
            ->orderBy('title');

        if (Auth::user()->isAuthor()) {
            $query->whereBelongsTo(Auth::user(), 'owner');
        }

        return $query->get();
    }

    #[Computed]
    public function owners()
    {
        return User::query()
            ->where('role', 'author')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function uniqueHandbookSlug(string $title): string
    {
        $slug = Str::slug($title);
        $suffix = 1;
        $candidate = $slug;

        while (Handbook::query()->where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function uniquePageSlug(Handbook $handbook, string $title): string
    {
        $slug = Str::slug($title);
        $candidate = $slug;
        $suffix = 1;

        while ($handbook->pages()->where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function copiedPageBody(string $body, Handbook $sourceHandbook, Handbook $copiedHandbook): string
    {
        return str_replace(
            "/storage/handbooks/{$sourceHandbook->id}/images/",
            "/storage/handbooks/{$copiedHandbook->id}/images/",
            $body,
        );
    }
}; ?>

<section class="w-full space-y-6">
    <div class="rounded-3xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm font-medium uppercase tracking-[0.3em] text-zinc-500 dark:text-zinc-400">Manage</p>
        <h1 class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">Handbook manager</h1>
        <p class="mt-3 max-w-3xl text-sm leading-7 text-zinc-600 dark:text-zinc-300">
            Admins can manage every handbook. Authors can manage the handbooks assigned to their account, including page order and markdown content.
        </p>
    </div>

    <div class="space-y-6">
        @if (auth()->user()->isAdmin())
            <div class="flex justify-end">
                <x-ui::link :href="route('admin.handbooks.create')" wire:navigate variant="dark" icon="plus">Create</x-ui::link>
            </div>

            @if ($duplicateSourceHandbookId)
                <div class="max-w-2xl rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <form wire:submit="duplicateHandbook" class="space-y-5">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">Duplicate handbook</p>
                            <h2 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">Create a copied handbook</h2>
                            <p class="mt-2 text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                                The new handbook will include copied pages, handbook images, and updated markdown image paths.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <x-ui::form.label for="duplicateTitle">Title</x-ui::form.label>
                            <x-ui::form.input wire:model="duplicateTitle" name="duplicateTitle" required />
                            <x-ui::form.error for="duplicateTitle" />
                        </div>

                        <div class="space-y-2">
                            <x-ui::form.label for="duplicateOwnerId">Owner</x-ui::form.label>
                            <x-ui::form.select
                                wire:model="duplicateOwnerId"
                                name="duplicateOwnerId"
                                :options="$this->owners->pluck('name', 'id')->all()"
                                :value="$duplicateOwnerId"
                                placeholder="Select an author"
                                required
                            />
                            <x-ui::form.error for="duplicateOwnerId" />
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-ui::button type="submit" variant="dark">Copy handbook</x-ui::button>
                            <x-ui::button type="button" wire:click="cancelDuplicate" variant="light">Cancel</x-ui::button>
                        </div>
                    </form>
                </div>
            @endif
        @endif

        <div class="space-y-4">
            @forelse ($this->handbooks as $handbook)
                <article wire:key="admin-handbook-{{ $handbook->id }}" class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $handbook->title }}</h2>
                            @if (filled($handbook->description))
                                <p class="mt-2 text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $handbook->description }}</p>
                            @endif
                            <p class="mt-3 text-xs font-medium uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">
                                Owner: {{ $handbook->owner?->name ?? 'Unassigned' }}
                            </p>
                        </div>

                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium uppercase tracking-[0.25em] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ $handbook->pages_count }} pages
                        </span>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <x-ui::link :href="route('admin.handbooks.edit', $handbook)" wire:navigate variant="dark">Edit</x-ui::link>
                        <x-ui::link :href="route('handbooks.show', ['handbook' => $handbook])" variant="light">View</x-ui::link>
                        @if (auth()->user()->isAdmin())
                            <x-ui::button wire:click="beginDuplicate({{ $handbook->id }})" type="button" variant="light">Copy</x-ui::button>
                        @endif
                        <x-ui::button wire:click="confirmDeleteHandbook({{ $handbook->id }})" type="button" variant="red">Delete</x-ui::button>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-zinc-300 bg-white p-8 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                    No handbooks yet.
                </div>
            @endforelse
        </div>
    </div>

    <div
        x-data="{ open: $wire.entangle('showDeleteHandbookModal') }"
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="open = false; $wire.cancelDeleteHandbook()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/60 px-4"
        style="display: none;"
    >
        <div class="absolute inset-0" x-on:click="open = false; $wire.cancelDeleteHandbook()"></div>

        <div class="relative z-10 w-full max-w-lg rounded-3xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <div class="space-y-3">
                <x-ui::heading level="4">Delete handbook?</x-ui::heading>
                <p class="text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                    This will permanently delete
                    <span class="font-semibold text-zinc-950 dark:text-zinc-50">{{ $handbookPendingDeletionTitle ?: 'this handbook' }}</span>
                    and its pages and images.
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui::button type="button" wire:click="cancelDeleteHandbook" variant="light">Cancel</x-ui::button>
                <x-ui::button type="button" wire:click="deleteHandbook" variant="red">Delete handbook</x-ui::button>
            </div>
        </div>
    </div>
</section>

<?php

use App\Models\Handbook;
//use App\Models\HandbookPage;
use App\Models\User;
use App\Support\HandbookDuplicator;
//use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
//use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Handbooks')] class extends Component {
   // use AuthorizesRequests;

    public ?int $duplicateSourceHandbookId = null;

    public ?int $handbookPendingDeletionId = null;

    public bool $handbookPendingDeletionOwnsSharedPages = false;

    public string $handbookPendingDeletionTitle = '';

    public string $duplicateTitle = '';

    public string $duplicateSourceHandbookTitle = '';

    public string $duplicateOwnerId = '';

    public int $duplicateLocalPageCount = 0;

    public int $duplicateSharedPageCount = 0;

    public bool $showDeleteHandbookModal = false;

    public string $deleteHandbookError = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Handbook::class);
    }

    public function confirmDeleteHandbook(int $handbookId): void
    {
        $handbook = Handbook::query()->findOrFail($handbookId);
        $this->authorize('delete', $handbook);

        $this->handbookPendingDeletionId = $handbook->id;
        $this->handbookPendingDeletionOwnsSharedPages = $this->handbookOwnsSharedPages($handbook);
        $this->handbookPendingDeletionTitle = $handbook->title;
        $this->showDeleteHandbookModal = true;
    }

    public function cancelDeleteHandbook(): void
    {
        $this->reset(
            'handbookPendingDeletionId',
            'handbookPendingDeletionOwnsSharedPages',
            'handbookPendingDeletionTitle',
            'showDeleteHandbookModal',
            'deleteHandbookError',
        );
    }

    public function deleteHandbook(): void
    {
        $handbook = Handbook::query()->findOrFail($this->handbookPendingDeletionId);
        $this->authorize('delete', $handbook);

        if ($this->handbookOwnsSharedPages($handbook)) {
            $this->deleteHandbookError = 'This handbook owns pages shared with other handbooks. Remove those shared positions before deleting the handbook.';

            return;
        }

        $handbook->delete();

        $this->cancelDeleteHandbook();
    }

    public function beginDuplicate(int $handbookId): void
    {
        $this->authorize('create', Handbook::class);

        $handbook = Handbook::query()
            ->with(['owner:id', 'positions.page'])
            ->findOrFail($handbookId);

        $this->duplicateSourceHandbookId = $handbook->id;
        $this->duplicateSourceHandbookTitle = $handbook->title;
        $this->duplicateTitle = "{$handbook->title} Copy";
        $this->duplicateOwnerId = (string) ($handbook->user_id ?? '');
        $this->duplicateLocalPageCount = $handbook->positions->filter(
            fn ($position) => $position->page->handbook_id === $handbook->id
        )->count();
        $this->duplicateSharedPageCount = $handbook->positions->count() - $this->duplicateLocalPageCount;

        $this->resetValidation(['duplicateTitle', 'duplicateOwnerId']);
    }

    public function cancelDuplicate(): void
    {
        $this->reset(
            'duplicateSourceHandbookId',
            'duplicateSourceHandbookTitle',
            'duplicateTitle',
            'duplicateOwnerId',
            'duplicateLocalPageCount',
            'duplicateSharedPageCount',
        );
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
                'positions.page.handbook',
                'images',
            ])
            ->findOrFail($validated['duplicateSourceHandbookId']);

        $copiedHandbook = app(HandbookDuplicator::class)->duplicate(
            $sourceHandbook,
            (int) $validated['duplicateOwnerId'],
            $validated['duplicateTitle'],
        );

        $this->cancelDuplicate();

        $this->redirect(route('handbooks.admin.edit', $copiedHandbook, absolute: false), navigate: true);
    }

    #[Computed]
    public function handbooks()
    {
        $query = Handbook::query()
            ->with(['owner:id,name'])
            ->withCount('positions')
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

    private function handbookOwnsSharedPages(Handbook $handbook): bool
    {
        return $handbook->ownsSharedPages();
    }
}; ?>

<section class="w-full space-y-6">
    <x-panel class="p-8">
        <p class="text-sm font-medium uppercase tracking-[0.3em] text-zinc-500 dark:text-zinc-400">Manage</p>
        <h1 class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">Handbook manager</h1>
        <p class="mt-3 max-w-3xl text-sm leading-7 text-zinc-600 dark:text-zinc-300">
            Admins can manage every handbook. Authors can manage the handbooks assigned to their account, including page order and markdown content.
        </p>
    </x-panel>

    <div class="space-y-6">
        @if (auth()->user()->isAdmin())
            <div class="flex justify-end">
                <x-ui::link :href="route('handbooks.admin.create')" wire:navigate variant="dark" icon="plus">Create</x-ui::link>
            </div>

            @if ($duplicateSourceHandbookId)
                <x-panel class="max-w-2xl p-6">
                    <form wire:submit="duplicateHandbook" class="space-y-5">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">Duplicate handbook</p>
                            <h2 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">Create a copied handbook</h2>
                            <p class="mt-2 text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                                The new handbook will include copied pages, handbook images, and updated markdown image paths.
                            </p>
                        </div>

                        <x-panel.inset>
                            <p class="font-medium text-zinc-950 dark:text-zinc-50">{{ $duplicateSourceHandbookTitle }}</p>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <div class="rounded-2xl border border-zinc-200/80 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                                    <p class="text-xs font-medium uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">Copied</p>
                                    <p class="mt-2 text-sm">{{ $duplicateLocalPageCount }} local pages will be duplicated into the new handbook.</p>
                                </div>
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-950 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                                    <p class="text-xs font-medium uppercase tracking-[0.25em] text-amber-700 dark:text-amber-300">Linked</p>
                                    <p class="mt-2 text-sm">{{ $duplicateSharedPageCount }} shared pages will remain linked to their source handbooks.</p>
                                </div>
                            </div>
                        </x-panel.inset>

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
                </x-panel>
            @endif
        @endif

        <div class="space-y-4 grid gap-5 md:grid-cols-1 xl:grid-cols-2">
            @forelse ($this->handbooks as $handbook)
                <x-panel wire:key="admin-handbook-{{ $handbook->id }}" class="p-6">
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
                            {{ $handbook->positions_count }} pages
                        </span>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <x-ui::link :href="route('handbooks.admin.edit', $handbook)" wire:navigate variant="dark">Edit</x-ui::link>
                        <x-ui::link :href="route('handbooks.show', ['handbook' => $handbook])" variant="light">View</x-ui::link>
                        @if (auth()->user()->isAdmin())
                            <x-ui::button wire:click="beginDuplicate({{ $handbook->id }})" type="button" variant="light">Copy</x-ui::button>
                        @endif
                        <x-ui::button wire:click="confirmDeleteHandbook({{ $handbook->id }})" type="button" variant="red">Delete</x-ui::button>
                    </div>
                </x-panel>
            @empty
                <x-panel.empty class="bg-white p-8 dark:bg-zinc-900">
                    No handbooks yet.
                </x-panel.empty>
            @endforelse
        </div>
    </div>

    <x-ui::modal
        name="delete-handbook-modal"
        :show="$showDeleteHandbookModal"
        maxWidth="lg"
        x-effect="if (! show && $wire.showDeleteHandbookModal) { $wire.cancelDeleteHandbook() }"
    >
        <x-slot:title>
            Delete handbook?
        </x-slot:title>

        <div class="space-y-3">
            <p class="text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                This will permanently delete
                <span class="font-semibold text-zinc-950 dark:text-zinc-50">{{ $handbookPendingDeletionTitle ?: 'this handbook' }}</span>
                and its pages and images.
            </p>

            @if ($handbookPendingDeletionOwnsSharedPages)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    Delete is blocked while this handbook owns pages shared with other handbooks. Remove those shared positions first, then try again.
                </div>
            @endif

            @if (filled($deleteHandbookError))
                <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $deleteHandbookError }}</p>
            @endif
        </div>

        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-ui::button type="button" wire:click="cancelDeleteHandbook" variant="light">Cancel</x-ui::button>
                <x-ui::button type="button" wire:click="deleteHandbook" variant="red">Delete handbook</x-ui::button>
            </div>
        </x-slot:footer>
    </x-ui::modal>
</section>

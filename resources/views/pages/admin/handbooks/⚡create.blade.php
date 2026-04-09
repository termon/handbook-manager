<?php

use App\Models\Handbook;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Create handbook')] class extends Component {
    use AuthorizesRequests;

    public string $title = '';

    public string $description = '';

    public string $ownerId = '';

    public function mount(): void
    {
        $this->authorize('create', Handbook::class);
    }

    public function createHandbook(): void
    {
        $this->authorize('create', Handbook::class);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'ownerId' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'author')],
        ]);

        $handbook = DB::transaction(function () use ($validated): Handbook {
            $handbook = Handbook::create([
                'user_id' => (int) $validated['ownerId'],
                'title' => $validated['title'],
                'slug' => $this->uniqueHandbookSlug($validated['title']),
                'description' => blank($validated['description']) ? null : $validated['description'],
            ]);

            $handbook->pages()->create([
                'title' => 'Introduction',
                'slug' => 'introduction',
                'position' => 0,
                'body' => "# Introduction\n\nStart writing the first page of your handbook here.",
            ]);

            return $handbook;
        });

        $this->redirect(route('admin.handbooks.edit', $handbook, absolute: false), navigate: true);
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
}; ?>

<section class="w-full space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.3em] text-zinc-500 dark:text-zinc-400">Manage / Handbook</p>
            <h1 class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">Create handbook</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-zinc-600 dark:text-zinc-300">
                Create a handbook and assign it to an author account. The editor will open immediately after creation.
            </p>
        </div>

        <x-ui::link :href="route('admin.handbooks.index')" wire:navigate variant="light">Back to handbooks</x-ui::link>
    </div>

    <div class="max-w-2xl rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <form wire:submit="createHandbook" class="space-y-5">
            <div class="space-y-2">
                <x-ui::form.label for="title">Title</x-ui::form.label>
                <x-ui::form.input wire:model="title" name="title" required />
                <x-ui::form.error for="title" />
            </div>

            <div class="space-y-2">
                <x-ui::form.label for="description">Description</x-ui::form.label>
                <x-ui::form.textarea wire:model="description" name="description" rows="4" />
                <x-ui::form.error for="description" />
            </div>

            <div class="space-y-2">
                <x-ui::form.label for="ownerId">Owner</x-ui::form.label>
                <x-ui::form.select
                    wire:model="ownerId"
                    name="ownerId"
                    :options="$this->owners->pluck('name', 'id')->all()"
                    :value="$ownerId"
                    placeholder="Select an author"
                    required
                />
                <x-ui::form.error for="ownerId" />
            </div>

            <x-ui::button type="submit" variant="dark">Create handbook</x-ui::button>
        </form>
    </div>
</section>

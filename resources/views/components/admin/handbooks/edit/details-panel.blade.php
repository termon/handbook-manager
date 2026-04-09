@props([
    'handbook',
    'canAssignOwner',
    'owners',
    'ownerId' => '',
    'isListed' => true,
])

<div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-2 border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <x-ui::heading level="4">Handbook details</x-ui::heading>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">Update the handbook title, description, owner, and public listing status.</p>
    </div>

    <form wire:submit="saveHandbook" class="mt-6 space-y-5">
        <div class="space-y-2">
            <x-ui::form.label for="handbookTitle">Title</x-ui::form.label>
            <x-ui::form.input wire:model="handbookTitle" name="handbookTitle" required />
            <x-ui::form.error for="handbookTitle" />
        </div>

        <div class="space-y-2">
            <x-ui::form.label for="handbookDescription">Description</x-ui::form.label>
            <x-ui::form.textarea wire:model="handbookDescription" name="handbookDescription" rows="4" />
            <x-ui::form.error for="handbookDescription" />
        </div>

        @if ($canAssignOwner)
            <div class="space-y-2">
                <x-ui::form.label for="ownerId">Owner</x-ui::form.label>
                <x-ui::form.select
                    wire:model="ownerId"
                    name="ownerId"
                    :options="$owners->pluck('name', 'id')->all()"
                    :value="$ownerId"
                    placeholder="Select an author"
                    required
                />
                <x-ui::form.error for="ownerId" />
            </div>
        @else
            <div class="space-y-2">
                <x-ui::form.label for="ownerName">Owner</x-ui::form.label>
                <x-ui::form.input name="ownerName" :value="$handbook->owner?->name ?? auth()->user()->name" disabled />
            </div>
        @endif

        <div class="space-y-2">
            <label for="isListed" class="flex items-start gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">
                <input id="isListed" type="checkbox" wire:model="isListed" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:ring-zinc-500" />
                <span>
                    <span class="block font-medium text-zinc-900 dark:text-zinc-50">Listed in public handbook directory</span>
                    <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">Uncheck to hide this handbook from the public index while keeping it accessible by direct link.</span>
                </span>
            </label>
            <x-ui::form.error for="isListed" />
        </div>

        <x-ui::button type="submit" variant="dark">Save handbook</x-ui::button>
    </form>
</div>

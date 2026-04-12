@props([
    'handbook',
    'canAssignOwner',
    'owners',
    'ownerId' => '',
    'isListed' => true,
])

<x-panel class="p-6">
    <div class="flex flex-col gap-2 border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <x-ui::heading level="4">Handbook details</x-ui::heading>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">Update the handbook title, description, owner, and public listing status.</p>
    </div>

    <form wire:submit="saveHandbook" class="mt-6 space-y-5">
        <x-ui::form.input-group wire:model="handbookTitle" name="handbookTitle" label="Title" required />

        <x-ui::form.textarea-group wire:model="handbookDescription" name="handbookDescription" label="Description" rows="4" />

        @if ($canAssignOwner)
            <x-ui::form.select-group
                wire:model="ownerId"
                name="ownerId"
                label="Owner"
                :options="$owners->pluck('name', 'id')->all()"
                :value="$ownerId"
                placeholder="Select an author"
                required
            />
        @else
            <x-ui::form.input-group
                name="ownerName"
                label="Owner"
                :value="$handbook->owner?->name ?? auth()->user()->name"
                disabled
            />
        @endif
      

        <x-ui::form.toggle-group
            wire:model="isListed"
            name="isListed"
            label="Listed in public handbook directory"
            description="Uncheck to hide this handbook from the public index while keeping it accessible by direct link."
            :checked="$isListed"
            variant="card"
        />

        <x-ui::button type="submit" variant="dark">Save handbook</x-ui::button>
    </form>
</x-panel>

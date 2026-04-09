@props([
    'handbook',
    'canAssignOwner',
    'owners',
    'ownerId' => '',
])

<div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-2 border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <x-ui::heading level="4">Handbook details</x-ui::heading>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">Update the handbook title, description, and assigned owner.</p>
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

        <x-ui::button type="submit" variant="dark">Save handbook</x-ui::button>
    </form>
</div>

<?php

use App\Models\Handbook;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Handbooks')] class extends Component {
    #[Computed]
    public function handbooks()
    {
        $query = Handbook::query()
            ->with(['pages' => fn ($query) => $query->orderBy('position')])
            ->orderBy('title');

        if (Auth::check() && Auth::user()->isAuthor()) {
            $query->whereBelongsTo(Auth::user(), 'owner');
        }

        return $query->get();
    }
}; ?>

<div>

    <section class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->handbooks as $handbook)
            @php($firstPage = $handbook->pages->first())

            <article wire:key="handbook-{{ $handbook->id }}" class="flex h-full flex-col rounded-4xl border border-neutral-200 bg-white/92 p-6 shadow-[0_24px_60px_-32px_rgba(84,84,84,0.16)] backdrop-blur">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-neutral-950">{{ $handbook->title }}</h2>
                        @if (filled($handbook->description))
                            <p class="mt-3 text-sm leading-7 text-neutral-600">{{ $handbook->description }}</p>
                        @endif
                    </div>

                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium uppercase tracking-[0.25em] text-neutral-600">
                        {{ str_pad((string) $handbook->pages->count(), 2, '0', STR_PAD_LEFT) }} pages
                    </span>
                </div>

                <div class="mt-6">
                    @if ($firstPage)
                        <a href="{{ route('handbooks.show', ['handbook' => $handbook, 'page' => $firstPage]) }}" class="inline-flex rounded-full border border-neutral-300 bg-neutral-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-700">
                            Open handbook
                        </a>
                    @else
                        <p class="text-sm text-neutral-500">This handbook does not have any pages yet.</p>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-4xl border border-dashed border-neutral-300 bg-white/78 p-8 text-sm text-neutral-600 md:col-span-2 xl:col-span-3">
                No handbooks have been created yet.
            </div>
        @endforelse
    </section>
</div>

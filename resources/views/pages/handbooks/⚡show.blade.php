<?php

use App\Models\Handbook;
use App\Models\HandbookPage;
use App\Models\HandbookPagePosition;
use App\Support\HandbookMarkdownRenderer;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.public')] class extends Component {
    public Handbook $handbook;

    public ?HandbookPagePosition $currentPosition = null;

    public ?HandbookPage $currentPage = null;

    public function mount(Handbook $handbook, ?string $pageSlug = null): void
    {
        $this->handbook = $handbook->load([
            'positions.page.handbook.images',
            'positions.page' => fn ($query) => $query->orderBy('position'),
        ]);

        $firstPosition = $this->handbook->positions->first();

        if ($pageSlug === null && $firstPosition !== null) {
            $this->redirectRoute('handbooks.show', [
                'handbook' => $this->handbook,
                'pageSlug' => $firstPosition->page->slug,
            ]);

            return;
        }

        $this->currentPosition = $this->handbook->positions
            ->first(fn (HandbookPagePosition $position): bool => $position->page?->slug === $pageSlug);

        if ($pageSlug !== null && $this->currentPosition === null) {
            abort(404);
        }

        $this->currentPosition ??= $firstPosition;
        $this->currentPage = $this->currentPosition?->page;
    }

    #[Computed]
    public function pageHtml(): HtmlString
    {
        if ($this->currentPage === null) {
            return new HtmlString('');
        }

        return new HtmlString(app(HandbookMarkdownRenderer::class)->render($this->handbook, $this->currentPage));
    }
}; ?>

<section
    x-data="{
        headings: [],
        collectHeadings() {
            const elements = this.$refs.content?.querySelectorAll('h2, h3, h4, h5, h6') ?? [];

            this.headings = Array.from(elements)
                .map((element) => {
                    if (! element.id) {
                        element.id = this.slugify(element.textContent || '');
                    }

                    return {
                        id: element.id,
                        level: Number(element.tagName.replace('H', '')),
                        text: (element.textContent || '').trim(),
                    };
                })
                .filter((heading) => heading.id && heading.text);
        },
        itemIndent(level) {
            return {
                'ps-0': level === 2,
                'ps-4': level === 3,
                'ps-8': level === 4,
                'ps-12': level === 5,
                'ps-16': level === 6,
            };
        },
        slugify(value) {
            return value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\\s-]/g, '')
                .replace(/\\s+/g, '-')
                .replace(/-+/g, '-');
        },
    }"
    x-init="$nextTick(() => collectHeadings())"
    class="grid w-full items-start gap-8 lg:grid-cols-[18rem_minmax(0,1fr)] xl:h-full xl:min-h-0 xl:grid-cols-[18rem_minmax(0,1fr)_16rem] xl:items-stretch xl:overflow-hidden"
>
    <aside class="rounded-4xl border border-neutral-200 bg-white p-6 text-neutral-900 shadow-[0_20px_60px_-36px_rgba(84,84,84,0.18)] backdrop-blur dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100 xl:h-full xl:overflow-y-auto">
        <div class="flex items-center gap-3 text-sm text-neutral-600 dark:text-neutral-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
            </svg>
            <a href="{{ route('handbooks.index') }}" class="text-sm font-medium uppercase tracking-[0.3em] text-neutral-500 dark:text-neutral-400">All handbooks</a>
        </div>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-neutral-950 dark:text-neutral-50">{{ $handbook->title }}</h1>

        @if (filled($handbook->description))
            <p class="mt-4 text-sm leading-7 text-neutral-600 dark:text-neutral-400">{{ $handbook->description }}</p>
        @endif

        <nav class="mt-8 space-y-2">
            @foreach ($handbook->positions as $position)
                <a
                    href="{{ route('handbooks.show', ['handbook' => $handbook, 'pageSlug' => $position->page->slug]) }}"
                    wire:key="public-position-{{ $position->id }}"
                    class="{{ $currentPosition?->is($position) ? 'border-neutral-300 bg-neutral-900 text-white dark:border-neutral-700 dark:bg-white dark:text-neutral-950' : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-300 dark:hover:bg-neutral-900' }} block rounded-2xl border px-4 py-3 text-sm font-medium transition"
                >
                    {{ $position->page->title }}
                </a>
            @endforeach
        </nav>
    </aside>

    <article class="rounded-4xl border border-neutral-200 bg-white px-6 py-8 text-neutral-950 shadow-[0_24px_70px_-38px_rgba(84,84,84,0.18)] dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-50 md:px-10 xl:h-full xl:overflow-y-auto">
        @if ($currentPage)
            <header class="border-b border-neutral-200 pb-6 dark:border-neutral-800">
                <p class="text-sm font-medium uppercase tracking-[0.3em] text-neutral-500 dark:text-neutral-400">{{ $handbook->title }}</p>
                <h2 class="mt-3 text-4xl font-semibold tracking-tight text-neutral-950 dark:text-neutral-50">{{ $currentPage->title }}</h2>
            </header>

            <div x-ref="content" class="prose prose-zinc mt-8 max-w-none prose-headings:tracking-tight prose-a:font-medium prose-a:text-neutral-950 prose-a:decoration-neutral-300 prose-a:underline-offset-4 prose-code:rounded prose-code:bg-neutral-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:text-[0.9em] prose-pre:rounded-2xl prose-pre:bg-neutral-950 prose-pre:text-neutral-100 dark:prose-invert dark:prose-a:text-neutral-50 dark:prose-a:decoration-neutral-600 dark:prose-code:bg-neutral-800">{!! $this->pageHtml !!}</div>
        @else
            <div class="rounded-3xl border border-dashed border-neutral-300 bg-white p-8 text-sm text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400">
                This handbook does not have any pages yet.
            </div>
        @endif
    </article>

    <aside
        x-cloak
        x-show="headings.length"
        class="hidden xl:block xl:h-full xl:overflow-y-auto"
        aria-label="Quick links"
    >
        <div class="rounded-4xl border border-neutral-200 bg-white p-6 text-neutral-900 shadow-[0_20px_60px_-36px_rgba(84,84,84,0.18)] dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-neutral-500 dark:text-neutral-400">Quick links</p>

            <ul class="mt-4 space-y-1">
                <template x-for="heading in headings" :key="heading.id">
                    <li :class="itemIndent(heading.level)">
                        <a
                            :href="'#' + heading.id"
                            class="block rounded-xl px-3 py-2 text-xs text-neutral-700 transition hover:bg-neutral-100 hover:text-neutral-950 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:hover:text-neutral-50"
                            x-text="heading.text"
                        ></a>
                    </li>
                </template>
            </ul>
        </div>
    </aside>
</section>

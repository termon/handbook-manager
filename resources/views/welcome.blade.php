<x-layouts.app>
    <x-ui::breadcrumb :crumbs="[
        'Home' => '/',
    ]" />

    <x-ui::divider>
        <x-ui::heading level="3">Home</x-ui::heading>
    </x-ui::divider>

    <section class="space-y-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-linear-to-br from-white via-slate-50 to-emerald-50 shadow-sm dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-emerald-950/40">
            <div class="grid gap-8 px-6 py-8 lg:grid-cols-[minmax(0,1.8fr)_minmax(18rem,1fr)] lg:px-8">
                <div class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-emerald-700 dark:text-emerald-300">Handbook workspace</p>
                    <div class="space-y-3">
                        <h2 class="text-3xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">Keep handbook editing, publishing, and support work moving.</h2>
                        <p class="max-w-2xl text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Use the handbook manager to maintain internal content, browse the public handbook library to review published material,
                            and open the help pages for markdown, images, and authoring guidance.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        @can('viewAny', App\Models\Handbook::class)
                            <x-ui::link :href="route('handbooks.admin.index')" wire:navigate variant="dark">Open handbook manager</x-ui::link>
                        @endcan

                        <x-ui::link :href="route('handbooks.index')" variant="light">Browse handbook library</x-ui::link>
                        <x-ui::link :href="route('help')" variant="light">Authoring help</x-ui::link>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/70 bg-white/80 p-5 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/80">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Useful routes</p>
                    <div class="mt-4 grid gap-3">
                        <a href="{{ route('handbooks.index') }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-emerald-300 hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:hover:border-emerald-700">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Public handbooks</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Review listed handbooks and open pages exactly as readers will see them.</p>
                        </a>
                        <a href="{{ route('help') }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-emerald-300 hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:hover:border-emerald-700">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Help documentation</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Find authoring notes for markdown, shared pages, and handbook images.</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>

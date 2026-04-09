@props([
    'page' => null,
    'availablePages' => collect(),
    'content' => '',
    'headings' => collect(),
    'breadcrumbs' => collect(),
    'showBreadcrumbs' => true,
    'showPagesList' => true,
    'showPageLinks' => false,
    'showQuickLinks' => true,
    'currentPage' => null,
    'class' => '',
    'contentClass' => '',
    'sidebarClass' => '',
])

<!-- Breadcrumbs -->
@if($showBreadcrumbs && $breadcrumbs)
    <x-ui::breadcrumb :crumbs="$breadcrumbs" />
@endif

<div class="flex flex-col lg:flex-row gap-2 min-h-screen {{ $class }}">

    @if($showPagesList)
        <!-- Help Pages List View -->
        <div class="mt-2 w-full space-y-8">
            <section class="rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-blue-50 px-6 py-8 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700 dark:text-blue-300">Documentation</p>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Help & Documentation</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Start with the three core help pages, then use feature notes for implementation-specific details.
                    </p>
                </div>
            </section>

            @php
                $rootPages = $availablePages->get('General Documentation', collect())->sortBy(fn ($page) => match ($page['slug']) {
                    'index' => 0,
                    'faq' => 1,
                    'help-docs' => 2,
                    default => 3,
                });
                $featureGroups = $availablePages->except('General Documentation');
            @endphp

            @if($rootPages->count() > 0)
                <section>
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Core Pages</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Project overview, starter-kit usage, and help-system notes.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-3">
                        @foreach($rootPages as $helpPage)
                            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-500">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-700 dark:bg-blue-950/60 dark:text-blue-200">
                                        {{ $helpPage['kind'] }}
                                    </span>
                                    <svg class="h-5 w-5 text-slate-300 transition group-hover:text-blue-500 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                                <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $helpPage['title'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $helpPage['description'] }}</p>
                                <a href="{{ route('help', ['page' => $helpPage['slug']]) }}" class="mt-5 inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-200">
                                    Open page
                                </a>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($featureGroups->count() > 0)
                @foreach($featureGroups as $category => $categoryPages)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="mb-5 flex items-center justify-between gap-4 border-b border-slate-200 pb-4 dark:border-slate-800">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $category }}</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $categoryPages->count() }} {{ Str::plural('page', $categoryPages->count()) }}</p>
                            </div>
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                Feature Notes
                            </span>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($categoryPages as $helpPage)
                                <article class="rounded-xl border border-slate-200/80 bg-slate-50 p-5 transition hover:border-blue-300 hover:bg-blue-50/60 dark:border-slate-800 dark:bg-slate-950 dark:hover:border-blue-500 dark:hover:bg-slate-950">
                                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $helpPage['title'] }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $helpPage['description'] }}</p>
                                    <a href="{{ route('help', ['page' => $helpPage['slug']]) }}" class="mt-4 inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-200">
                                        Open page
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @endif

            @if($availablePages->count() === 0)
                <div class="text-center py-12">
                        <div class="text-gray-500 dark:text-gray-400">
                            <h3 class="text-lg font-medium mb-2">No help pages found</h3>
                            <p>No help documentation is currently available.</p>
                        </div>
                </div>
            @endif
        </div>
    @else
        <!-- Single Page Content View -->
        <article class="{{ $contentClass }}">
            {!! $content !!}
        </article>

        <!-- Sidebar -->
        @if($showPageLinks || $showQuickLinks)
            <aside class="{{ $sidebarClass }}" aria-label="HelpSidebar">
                
                <!-- Available Pages -->
                @if($showPageLinks && $availablePages->sum(fn ($categoryPages) => $categoryPages->count()) > 1)
                    <div class="mb-6">
                        <span class="text-blue-900 dark:text-blue-200 font-bold">Help Pages</span>
                        <ul class="px-1 mt-2">
                            @foreach ($availablePages as $category => $categoryPages)
                                @if($availablePages->count() > 1)
                                    <li class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-3 mb-1">
                                        {{ $category }}
                                    </li>
                                @endif
                                @foreach($categoryPages as $helpPage)
                                    <li class="text-sm py-1 rounded hover:bg-gray-200 dark:text-slate-100 dark:hover:bg-gray-800 {{ $helpPage['slug'] === $currentPage ? 'bg-blue-100 dark:bg-blue-900 font-semibold' : '' }}">
                                        <a href="{{ route('help', ['page' => $helpPage['slug']]) }}">{{ $helpPage['title'] }}</a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Quick Links (Table of Contents) -->
                @if($showQuickLinks && count($headings) > 0)
                    <div>
                        <span class="text-md text-blue-900 dark:text-blue-200 font-bold">Quick Links</span>
                        <ul class="px-1 mt-2">
                            @foreach ($headings as $heading)
                                @php
                                    $indent = match ($heading['tag']) {
                                        'h2' => 1,
                                        'h3' => 2,
                                        'h4' => 3,
                                        'h5' => 4,
                                        default => 1,
                                    };
                                    $css = $heading['tag'] === 'h2' ? 'font-bold' : '';
                                @endphp
                                <li class="text-sm {{ match ($indent) { 1 => 'ml-1', 2 => 'ml-2', 3 => 'ml-3', 4 => 'ml-4', default => 'ml-1' } }} py-1 rounded hover:bg-gray-200 dark:text-slate-100 dark:hover:bg-gray-800 {{ $css }}">
                                    <a href="#{{ $heading['id'] }}">{{ $heading['text'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        @endif
    @endif

</div>

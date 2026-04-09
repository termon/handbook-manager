<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>
        {{ filled($title ?? null) ? $title . ' - ' . config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
    </title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen overflow-hidden bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-50">
    <div
        class="relative h-screen overflow-x-hidden bg-[radial-gradient(circle_at_top,#fffefb_0%,#faf9f6_34%,#f3f1ec_100%)] dark:bg-[radial-gradient(circle_at_top,#171717_0%,#111111_34%,#090909_100%)]">
        <div
            class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.72),transparent_30%,rgba(224,220,212,0.26)_100%)] dark:bg-[linear-gradient(135deg,rgba(255,255,255,0.04),transparent_30%,rgba(255,255,255,0.02)_100%)]">
        </div>
        <div
            class="absolute inset-x-0 top-0 h-64 bg-[linear-gradient(180deg,rgba(255,255,255,0.7),transparent)] dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.08),transparent)]">
        </div>

        <div
            class="relative mx-auto grid h-full w-full max-w-7xl grid-rows-[auto_minmax(0,1fr)] px-6 py-8 xl:max-w-[108rem] 2xl:max-w-[120rem] lg:px-10">
            <header
                class="flex flex-col gap-4 border-b border-neutral-300/80 pb-6 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <x-app-logo-icon class="w-36 text-gray-900 dark:text-gray-50" />
                    <a href="{{ route('home') }}"
                        class="text-lg font-semibold tracking-[0.2em] text-neutral-950 uppercase dark:text-neutral-50">
                        {{ config('app.name', 'Placement Handbooks') }}
                    </a>
                    <p class="mt-2 max-w-2xl text-sm text-neutral-600 dark:text-neutral-400">Ordered markdown handbooks
                        for internal authors and public readers.</p>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('admin.handbooks.index') }}"
                            class="rounded-full border border-neutral-300 bg-white/85 px-4 py-2 text-sm font-medium text-neutral-900 transition hover:border-neutral-400 hover:bg-white dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:hover:border-neutral-600 dark:hover:bg-neutral-800">
                            Manage handbooks
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="rounded-full border border-neutral-300 bg-white/85 px-4 py-2 text-sm font-medium text-neutral-900 transition hover:border-neutral-400 hover:bg-white dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:hover:border-neutral-600 dark:hover:bg-neutral-800">
                            Log in
                        </a>
                    @endauth
                </div>
            </header>

            <main class="min-h-0 overflow-y-auto py-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts

</body> 

</html>

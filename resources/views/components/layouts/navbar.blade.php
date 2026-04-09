<x-ui::navbar class="text-sm">
    {{-- Brand Icon --}}
    <x-slot:brand-icon>
       <x-app-logo-icon class="w-36 fill-current text-black dark:text-white"  />
    </x-slot:brand-icon>

    {{-- Brand Title --}}
    <x-slot:brand-title>
        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ config('app.name') }}
        </span>
    </x-slot:brand-title>

    {{-- Top Navbar --}}
    <x-slot:navigation>
        <x-ui::navbar.link href="/" icon="home" label="Home" />
        <x-ui::navbar.link href="/admin/handbooks" icon="cog"  label="Handbooks" />
      
        {{-- <x-ui::navbar.dropdown label="Information" icon="folder">
            @can('view', App\Models\User::class)
                <x-ui::navbar.link href="{{ route('users.index') }}" icon="user" label="Users" />
            @endcan
            <x-ui::navbar.link label="UI Demo" :href="route('ui-demo')" icon="chart" />
            <x-ui::navbar.link href="/about" icon="info" label="About Us" />
             <x-ui::navbar.link href="/contact" icon="envelope" label="Contact Us" />
        </x-ui::navbar.dropdown> --}}
    </x-slot:navigation>

    {{-- Top Right Navbar (optional) --}}
    <x-slot:right>
        @auth
            <x-ui::navbar.dropdown label="{{ auth()->user()->name }}" icon="user">
                <x-ui::navbar.link label="Help" :href="route('help')" icon="info" />
                <x-ui::navbar.link icon="cog" label="Profile" href="/settings/profile" />       
                <x-ui::navbar.form-link action="/logout" icon="arrow-left" method="post" label="Logout" />
            </x-ui::navbar.dropdown>
        @endauth
    </x-slot:right>

    {{-- Bottom Toolbar --}}
    <x-slot:toolbar>
        <x-ui::navbar.link @click="dark = !dark" icon="moon" />
        <x-ui::navbar.link :href="route('help')" icon="info" />
        @impersonating
            <x-ui::navbar.link class="text-red-600 font-bold" icon="exit" href="{{ route('users.mirror.stop') }}" />
        @endimpersonating
    </x-slot:toolbar>
</x-ui::navbar>

<main class="min-h-screen bg-white px-4 pt-30 pb-20 mx-auto  text-gray-900 dark:bg-gray-900 dark:text-gray-100">
    {{ $slot }}
</main>

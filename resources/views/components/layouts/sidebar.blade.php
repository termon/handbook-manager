<x-ui::sidebar>

    {{-- Brand Icon always displayed --}}
    <x-slot:brand-icon>
        <div x-show="!collapsed" x-cloak>
            <x-app-logo-icon class="w-36 fill-current text-black dark:text-white"  />
        </div>
        <div x-show="collapsed" x-cloak>
            <x-app-logo-icon viewbox="0 0 1480 749" class="w-16 fill-current text-black dark:text-white"  />       
        </div>
    </x-slot:brand-icon>

    {{-- Brand Title  (optional and hidden when sidebar collapsed) --}}
    {{-- <x-slot:brand-title>
        <span class="mt-2 text-base font-light  text-slate-700 dark:text-slate-100">
            {{ config('app.name') }}
        </span>
    </x-slot:brand-title> --}}

    {{-- Primary Navigation --}}
    <x-slot:navigation>
        <x-ui::sidebar.link href="/" icon="home" label="Home" />
        <x-ui::sidebar.link href="/admin/handbooks" icon="computer-desktop"  label="Handbooks" />
        
        {{-- <x-ui::sidebar.dropdown label="Information" icon="folder">
            <x-ui::sidebar.link href="/about" icon="info" label="About Us" />
             <x-ui::sidebar.link href="/contact" icon="mail" label="Contact Us" />      
        </x-ui::sidebar.dropdown>       --}}
    </x-slot:navigation>

    {{-- Secondary Navigation (Optional) --}}
    <x-slot:secondary>
        @can('view', App\Models\User::class)
          <x-ui::sidebar.link href="/users" icon="user"  label="Users" />
        @endcan
    </x-slot:secondary>

    {{-- User Section - displays in toolbar when in mobile mode --}}
    <x-slot:user>
        @guest
            <x-ui::sidebar.link icon="user" href="{{ route('login') }}" label="Login" />
        @endguest
        @auth         
            <x-ui::sidebar.dropdown label="{{ auth()->user()->name }}" icon="user">  
                @can('view', App\Models\User::class)
                    <x-ui::sidebar.link href="{{ route('users.index') }}" icon="user" label="Users" />
                @endcan                    
                <x-ui::sidebar.link icon="cog" label="Profile" href="/settings/profile" />
                <x-ui::sidebar.form-link :action="route('logout')" icon="exit" method="post" label="Logout" />
            </x-ui::sidebar.dropdown>           
        @endauth
    </x-slot:user>

    {{-- Toolbar --}}
    <x-slot:toolbar>
        <x-ui::sidebar.link @click="dark = !dark" icon="moon" />
        <x-ui::sidebar.link :href="route('help')" icon="info" />
        @impersonating
            <x-ui::sidebar.link class="text-red-600 font-bold" icon="exit" href="{{ route('users.mirror.stop') }}" />
        @endimpersonating
    </x-slot:toolbar>

    {{-- Main content slot --}}
    {{ $slot }}

</x-ui::sidebar>

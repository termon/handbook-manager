<div class="w-full md:w-64 shrink-0 md:border-r border-gray-200 dark:border-gray-700 md:pr-4">
    <nav class="flex flex-row md:flex-col gap-1 md:divide-y md:divide-gray-200 md:dark:divide-gray-700">
        <div class="flex-1 md:flex-none px-2 py-2">
            <x-ui::link href="{{ route('settings.profile.edit') }}" :variant="request()->routeIs('settings.profile.*') ? 'light' : 'link'" class="w-full block text-center md:text-left"> 
                Profile
            </x-ui::link>                
        </div>
        <div class="flex-1 md:flex-none px-2 py-2">
            <x-ui::link href="{{ route('settings.password.edit') }}" :variant="request()->routeIs('settings.password.*') ? 'light' : 'link'" class="w-full block text-center md:text-left">
                Password
            </x-ui::link>
        </div>
        <div class="flex-1 md:flex-none px-2 py-2">
            <x-ui::link href="{{ route('settings.appearance.edit') }}" :variant="request()->routeIs('settings.appearance.*') ? 'light' : 'link'" class="w-full block text-center md:text-left">
                Appearance
            </x-ui::link>
        </div>
    </nav>   
</div>

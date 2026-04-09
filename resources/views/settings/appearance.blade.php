<x-layouts.app>
    <x-ui::breadcrumb :crumbs="[
        'Home' => route('home'),
        'Settings' => route('settings.profile.edit'),
        'Appearance' => '#',
    ]" />

    <x-ui::divider>
        <x-ui::heading level="3">Appearance</x-ui::heading>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Update the appearance settings for your account
        </p>
    </x-ui::divider>

    <div class="p-6">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar Navigation -->
            @include('settings._navigation')

            <!-- Appearance Content -->
            <div class="flex-1">
                <x-ui::card class="mb-6">
                    <!-- Theme Selection -->
                    <div class="mb-4">
                        <x-ui::form.label for="theme">Theme</x-ui::form.label>
                        <div class="inline-flex rounded-md shadow-sm mt-1" role="group">
                            <x-ui::button variant="light" @click="dark = false"
                                class="rounded-r-none border-r-0">
                                Light
                            </x-ui::button>
                            <x-ui::button variant="light" @click="dark = true" 
                                class="rounded-none border-r-0">
                                Dark
                            </x-ui::button>
                            <x-ui::button variant="light" @click="dark = system" 
                                class="rounded-l-none">
                                System
                            </x-ui::button>
                        </div>
                    </div>
                </x-ui::card>
            </div>
        </div>
    </div>
</x-layouts.app>

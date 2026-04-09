<!-- This tab view could be used to replace all other existing views in the settings folder -->

<x-layouts.app>
    <x-ui::breadcrumb :crumbs="[
        'Home' => route('home'),
        'Settings' => null,
    ]" />

    <x-ui::divider>
        <x-ui::heading level="3">Settings</x-ui::heading>               
    </x-ui::divider>

    
    <x-ui::tabs active="Profile">
        <x-ui::tabs.tab name="Profile" href="{{ route('settings.profile.edit') }}" >
            <div class="flex-1">
                <x-ui::card class="mb-6">
                    <!-- Profile Form -->
                    <form class="max-w-md mb-10" action="{{ route('settings.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @if ($user->avatar_exists)
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Current avatar</p>
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }} avatar"
                                    class="h-16 w-16 rounded-full object-cover border border-gray-200 dark:border-gray-700">
                            </div>
                        @endif

                        <div class="mb-4">
                            <x-ui::form.input-group label="Name" name="name" type="text"
                                value="{{ old('name', $user->name) }}" />
                        </div>

                        <div class="mb-6">
                            <x-ui::form.input-group label="Email" name="email" type="email"
                                value="{{ old('email', $user->email) }}" />
                        </div>

                        <div class="mb-6">
                            <x-ui::form.input-group label="Avatar" name="avatar" type="file" accept="image/*" />
                        </div>

                        <div>
                            <x-ui::button variant="dark" type="submit">Save</x-ui::button>
                        </div>
                    </form>

                    <!-- Delete Account Section -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                        <x-ui::heading level="3" class="mb-1">
                            Delete account
                        </x-ui::heading>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Delete your account and all of its resources
                        </p>
                        <form action="{{ route('settings.profile.destroy') }}" method="POST"
                            onsubmit="return confirm('Are you sure you want to delete your account?')">
                            @csrf
                            @method('DELETE')
                            <x-ui::button variant="red" type="submit">Delete account</x-ui::button>
                        </form>
                    </div>
                </x-ui::card>
            </div>
        </x-ui::tabs.tab>
        <x-ui::tabs.tab name="Password" href="{{ route('settings.password.edit') }}" >
            <!-- Password Content -->
            <div class="flex-1">
                <x-ui::card class="mb-6">
                    <!-- Password Form -->
                    <form class="max-w-md mb-10" action="{{ route('settings.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <x-ui::form.input-group label="Current Password" name="current_password" type="password" />
                        </div>

                        <div class="mb-6">
                            <x-ui::form.input-group label="New Password" name="password" type="password" />
                        </div>

                        <div class="mb-6">
                            <x-ui::form.input-group label="Confirm Password" name="password_confirmation" type="password" />
                        </div>

                        <div>
                            <x-ui::button variant="dark" type="submit">Update Password</x-ui::button>
                        </div>
                    </form>
                </x-ui::card>
            </div>
        </x-ui::tabs.tab>
        <x-ui::tabs.tab name="Appearance" href="{{ route('settings.appearance.edit') }}" >
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
        </x-ui::tabs.tab>
 
    </x-ui::tabs>

</x-layouts.app>

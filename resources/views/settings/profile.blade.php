<x-layouts.app>
    <x-ui::breadcrumb :crumbs="[
        'Home' => route('home'),
        'Settings' => route('settings.profile.edit'),
        'Profile' => '#',
    ]" />

    <x-ui::divider>
        <x-ui::heading level="3">Profile</x-ui::heading>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Update your name, email address, and avatar</p>
    </x-ui::divider>

    <div class="p-6">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar Navigation -->
            @include('settings._navigation')

            <!-- Profile Content -->
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
                        <form action="{{ route('settings.profile.destroy') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <x-ui::form.confirm>Delete account</x-ui::form.confirm>
                        </form>
                    </div>
                </x-ui::card>
            </div>
        </div>
    </div>
</x-layouts.app>

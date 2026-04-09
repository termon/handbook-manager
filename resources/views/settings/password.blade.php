<x-layouts.app>
    <x-ui::breadcrumb :crumbs="[
        'Home' => route('home'),
        'Settings' => route('settings.profile.edit'),
        'Password' => '#',
    ]" />

    <x-ui::divider>
        <x-ui::heading level="3">Update password</x-ui::heading>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Ensure your account uses a fully random password to stay secure
        </p>
    </x-ui::divider>

    <div class="p-6">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar Navigation -->
            @include('settings._navigation')

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
        </div>
    </div>
</x-layouts.app>

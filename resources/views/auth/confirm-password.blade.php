<x-layouts.app>
    <!-- Confirm Password Card -->
    <x-ui::card>
        <div class="p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Confirm Password</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Please confirm your password before continuing.
                </p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf
                <!-- Password Input -->
                <div class="mb-4">
                    <x-ui::form.input-group name="password" type="password" label="Password" placeholder="••••••••" />
                </div>

                <!-- Confirm Button -->
                <x-ui::button variant="dark" type="submit" class="w-full mt-6">
                    Confirm Password
                </x-ui::button>
            </form>

            <!-- Forgot Password Link -->
            <div class="text-center mt-6">
                <x-ui::link href="{{ route('password.request') }}">Forgot your password?</x-ui::link>
            </div>
        </div>
    </x-ui::card>
</x-layouts.app>

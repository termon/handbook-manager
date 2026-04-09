<x-layouts.guest>
    <!-- Forgot Password Card -->
    <x-ui::card class="bg-white w-md md:w-xl">

        <div class="flex justify-center mt-4">
            <svg class="" width="28" height="28" viewBox="0 0 24 24" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M12 15V17M6 21H18C19.1046 21 20 20.1046 20 19V13C20 11.8954 19.1046 11 18 11H6C4.89543 11 4 11.8954 4 13V19C4 20.1046 4.89543 21 6 21ZM16 11V7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7V11H16Z"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </div>

        <x-ui::heading level="4" class="text-center mt-4">
            <p>Forgot Password</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ __('Enter your email to receive a password reset link') }}</p>
        </x-ui::heading>


        <div class="p-6">
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <!-- Email Input -->
                <div class="mt-2">
                    <x-ui::form.input-group label="Email" name="email" type="email" placeholder="your@email.com" />
                </div>

                <!-- Send Reset Link Button -->
                <x-ui::button variant="dark" type="submit" class="w-full mt-6">
                    Send Password Reset Link
                </x-ui::button>
            </form>

            <!-- Back to Login Link -->
            <div class="text-center mt-6">
                <x-ui::link href="{{ route('login') }}">Back to login</x-ui::link>
            </div>
        </div>
    </x-ui::card>
</x-layouts.guest>

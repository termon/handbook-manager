<x-layouts.guest>
    <!-- Reset Password Card -->
   <x-ui::card>
        <div class="p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Reset Password</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Enter your email and new password below.</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ request()->route('token') }}">

                <!-- Email Input -->
                <div class="mb-4">
                    <x-ui::form.input name="email" type="email" label="Email"
                        value="{{ old('email', request('email')) }}" placeholder="your@email.com" />
                </div>

                <!-- Password Input -->
                <div class="mb-4">
                    <x-ui::form.input name="password" type="password" label="Password" placeholder="••••••••" />
                </div>

                <!-- Confirm Password Input -->
                <div class="mb-4">
                    <x-ui::form.input name="password_confirmation" type="password" label="Confirm Password"
                        placeholder="••••••••" />
                </div>

                <!-- Reset Password Button -->
                <x-ui::button variant="dark" type="submit" class="w-full">
                    Reset Password
                </x-ui::button>
            </form>

            <!-- Back to Login Link -->
            <div class="text-center mt-6">
                <x-ui::link href="{{ route('login') }}">Back to login</x-ui::link>
            </div>
        </div>
   </x-ui::card>
</x-layout.auth>

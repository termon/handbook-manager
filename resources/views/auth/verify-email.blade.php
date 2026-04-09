<x-layouts.app>
    <!-- Verify Email Card -->
    <x-ui::card>

        <div class="p-6">
            <div class="text-center mb-6">
                <x-ui::heading level="3">Verify Your Email Address
                </x-ui::heading>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Before proceeding, please check your email for a verification link.<br>
                    If you did not receive the email, you can request another below.
                </p>
            </div>

            @if (session('status') === 'verification-link-sent')
                <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                    A new verification link has been sent to your email address.
                </div>
            @endif


            <div class="flex flex-col items-center gap-4 mt-4">

                <form method="POST" action="{{ route('verification.store') }}">
                    @csrf
                    <x-ui::button variant="light">
                        Resend Verification Email
                    </x-ui::button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui::button variant="dark">
                        Log out
                    </x-ui::button>
                </form>
            </div>
        </div>
    </x-ui::card>

</x-layouts.app>


<x-layouts.app>

  <x-ui::card class="bg-white w-md md:w-3xl dark:bg-gray-800 shadow-lg p-6 mx-auto mt-10">

        <div class="flex justify-center mt-4">
            <svg class="" width="28" height="28" viewBox="0 0 24 24" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M12 15V17M6 21H18C19.1046 21 20 20.1046 20 19V13C20 11.8954 19.1046 11 18 11H6C4.89543 11 4 11.8954 4 13V19C4 20.1046 4.89543 21 6 21ZM16 11V7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7V11H16Z"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </div>

        <x-ui::heading level="3" class="text-center mt-4">
           Update User Details
        </x-ui::heading>


        <form method="POST" action="{{ route('users.update', $user->id) }}" enctype="multipart/form-data">
            @csrf

            <!-- name -->
            <div class="mt-2">
                <x-ui::form.input-group label="Name" name="name" type="text" value="{{ old('name', $user->name) }}" />
            </div>

            <!-- email -->
            <div class="mt-2">
                <x-ui::form.input-group label="Email" name="email" type="email" value="{{ old('email', $user->email) }}" />
            </div>

              <!-- role -->
            <div class="mt-2">
                <x-ui::form.select-group label="Role" name="role" :options="\App\Enums\Role::options()" value="{{ old('role', $user->role) }}" />
            </div>

             <div class="mt-2 flex justify-between">
                <x-ui::form.input-group label="Avatar" name="avatar" type="file" accept="image/*" />
                <img src="{{ $user->avatar_url }}" class="w-24 rounded"/>
            </div>

            <!-- password -->
            {{-- <div class="flex gap-2  <div class="w-full">
                <x-ui::form.input label="Password" name="password" type="password" value="{{ old('password', $user->password) }}" />
            </div> --}}

            <!-- submit -->
            <div class="mt-4 flex gap-2">
                <x-ui::button variant="dark" type="submit">Update</x-ui::button>
                <x-ui::link link="light" href="{{ route('users.index') }}">Cancel</x-ui::link>
            </div>

        </form>
    </x-ui::card>

</x-layouts.guest>


<x-layouts.app>

    <x-ui::breadcrumb :crumbs="['Home' => route('home'), 'Users' => route('users.index')]" />

    <x-ui::divider>
        <x-ui::heading level="3">User Management</x-ui::heading>
    </x-ui::divider>

    @include('users._search')

    <x-ui::table>
        <x-slot:thead>
            <x-ui::table.tr>
                <x-ui::table.th>
                    <x-ui::link-sort name="id">Id</x-ui::link-sort>
                </x-ui::table.th>
                <x-ui::table.th>
                    <x-ui::link-sort name="name">Name</x-ui::link-sort>
                </x-ui::table.th>
                <x-ui::table.th>
                    <x-ui::link-sort name="email">Email</x-ui::link-sort>
                </x-ui::table.th>
                <x-ui::table.th>
                    <x-ui::link-sort name="role">Role</x-ui::link-sort>
                </x-ui::table.th>
                <x-ui::table.th>Actions</x-ui::table.th>
            </x-ui::table.tr>
        </x-slot:thead>

        <x-slot:tbody>
            @foreach ($users as $user)
                <x-ui::table.tr>
                    <x-ui::table.td>{{ $user->id }}</x-ui::table.td>
                    <x-ui::table.td>{{ $user->name }}</x-ui::table.td>
                    <x-ui::table.td>{{ $user->email }}</x-ui::table.td>
                    <x-ui::table.td>{{ $user->role }}</x-ui::table.td>
                    <x-ui::table.td class="flex gap-2">
                        @if ($user->canBeImpersonated())
                            <x-ui::link variant='none' href="{{ route('users.mirror.start', $user->id) }}"
                                title="impersonate user">
                                <x-ui::svg icon="finger-print" size="sm" />
                            </x-ui::link>
                        @endif
                        <x-ui::link variant='none' href="{{ route('users.edit', $user->id) }}" title="edit user">
                            <x-ui::svg icon="edit" size="sm" />
                        </x-ui::link>
                    </x-ui::table.td>
                </x-ui::table.tr>
            @endforeach
        </x-slot:tbody>
    </x-ui::table>

    <x-ui::paginator :items="$users" class="mt-4"/>

</x-layouts.app>

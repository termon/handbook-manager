<x-layouts.app>

    <x-ui::breadcrumb :crumbs="[
        'Home' => route('home'),
        'Contact' => '',
    ]" />
  
    <x-ui::divider>
        <x-ui::heading level="3">Contact</x-ui::heading>
    </x-ui::divider>

</x-layouts.app>

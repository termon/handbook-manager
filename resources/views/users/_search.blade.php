<form method="GET" :action="route('users.index')" class="form flex gap-2 items-center my-4">
    @foreach(request()->except(['search', 'page']) as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach

    <x-ui::form.input placeholder="search..." name="search" value="{{ $search }}" class="text-xs" />
    
    <x-ui::button variant="dark" type="submit" class="text-xs flex gap-2"> 
        <x-ui::svg icon="search"/>       
        <span>Search</span>
    </x-ui::button>
    <x-ui::link variant="light" class="text-xs" href="{{ route('users.index') }}">Clear</x-ui::link>
</form>

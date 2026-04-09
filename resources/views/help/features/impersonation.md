# Impersonation

This project demos `franbarbalopez/mirror` through the user-management flow.

## Model setup

`User` uses the package trait:

```php
use Mirror\Concerns\Impersonatable;

class User extends Authenticatable
{
    use Impersonatable;
}
```

The model also defines the local rules:

```php
public function canImpersonate(): bool
{
    return $this->role === Role::ADMIN;
}

public function canBeImpersonated(): bool
{
    return $this->role !== Role::ADMIN;
}
```

## Controller usage

`UserController::start()`:

- loads the target user
- checks `Auth::user()->canImpersonate()`
- checks `$user->canBeImpersonated()`
- calls `Mirror::start($user)`

`UserController::stop()`:

- checks `Mirror::isImpersonating()`
- calls `Mirror::stop()`

## View usage

The users table shows an impersonate action for impersonatable users:

```blade
@if ($user->canBeImpersonated())
    <x-ui::link variant='none' href="{{ route('users.mirror.start', $user->id) }}" title="impersonate user">
        <x-ui::svg icon="finger-print" size="sm" />
    </x-ui::link>
@endif
```

The sidebar toolbar shows a stop action while impersonation is active:

```blade
@impersonating
    <x-ui::sidebar.link class="text-red-600 font-bold" icon="exit" href="{{ route('users.mirror.stop') }}" />
@endimpersonating
```

## Routes

- `users.mirror.start`
- `users.mirror.stop`

Use this pattern when admins need to temporarily assume another user's session for support or troubleshooting.

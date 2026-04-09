# Traits

This project includes these reusable traits in `app/Traits`.

## FileUpload

Used by `User` for `avatar`.

- handles uploaded files, URLs, and base64 image data
- provides helpers such as `fileUrl('avatar')`
- provides dynamic accessors such as `avatar_url`

Current model setup:

```php
protected function fileUploads(): array
{
    return [
        'avatar' => ['as_base64' => true],
    ];
}
```

Current controller validation:

```php
'avatar' => ['nullable', 'image', 'max:2048'],
```

Current view usage:

```blade
<img src="{{ $user->avatar_url }}" class="w-24 rounded"/>
```

Use it when a model needs simple upload persistence without repeating storage and cleanup logic.

## Searchable

Used by `User`.

- adds `scopeSearch(...)`
- supports simple multi-column text search

Current use:

```php
User::search($search, ['name', 'email', 'role'])
```

The trait is a good fit for small admin listings where a single search box should match a few columns without introducing a larger search package.

## Sortable

Included for reusable sorting logic.

- supports direct columns
- supports simple relation columns
- not currently used by `UserController`

Example:

```php
Post::sortable('title', 'asc')->get();
Post::sortable('author.name', 'desc')->get();
```

Use it when sorting rules should live with the model query layer instead of being duplicated in controllers.

## EnumOptions

Used by `App\Enums\Role`.

- `options()` returns form-friendly enum options
- `values()` returns enum values

Current form usage:

```blade
<x-ui::form.select-group
    label="Role"
    name="role"
    :options="\App\Enums\Role::options()"
    value="{{ old('role', $user->role) }}"
/>
```

This keeps enum-backed select inputs and validation lists concise.

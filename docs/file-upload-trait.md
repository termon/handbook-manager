# FileUpload Trait Guide

`App\Traits\FileUpload` adds reusable file-handling behaviour to Eloquent models.

It supports:
- uploaded files via `UploadedFile`
- externally hosted file URLs
- base64-encoded image data
- automatic cleanup of replaced and deleted stored files
- helper methods and dynamic accessors for rendering and download links

## What the Trait Does

When a model uses the trait, it will:
- process configured file attributes during `saving`
- delete previously stored files when a file attribute is replaced
- delete stored files when the model is deleted
- expose helper methods such as `fileUrl()` and `fileName()`
- expose dynamic accessors such as `file_url` and `file_name`

The trait does not validate uploads for you. Validation should still happen in your controller or form request.

## Basic Setup

Add the trait to a model and implement `fileUploads()`:

```php
use App\Traits\FileUpload;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use FileUpload;

    protected function fileUploads(): array
    {
        return [
            'image' => ['as_base64' => true],
        ];
    }
}
```

Your database table must include a nullable column for each configured file attribute.

Examples:

```php
$table->longText('image')->nullable();
$table->string('avatar_path')->nullable();
```

Use `longText` when the attribute may store base64 image data. A normal `string` or `text` column is usually sufficient for storage paths or URLs.

## Configuring `fileUploads()`

`fileUploads()` must return an array of one or more configured attributes.

### Simplest form

```php
protected function fileUploads(): array
{
    return [
        'image',
    ];
}
```

This uses the default configuration:
- `disk`: `public`
- `folder`: the model table name
- `as_base64`: `false`

For a `User` model using the `users` table, an uploaded avatar would be stored on the `public` disk inside the `users` folder.

### Full configuration

```php
protected function fileUploads(): array
{
    return [
        'image' => [
            'disk' => 'public',
            'folder' => 'users/avatars',
            'as_base64' => true,
        ],
        'avatar_backup' => [
            'disk' => 'public',
            'folder' => 'users/avatar-backups',
            'as_base64' => false,
        ],
    ];
}
```

### Configuration options

Each attribute supports:
- `disk`: Laravel filesystem disk name used for stored files
- `folder`: destination folder for stored uploads
- `as_base64`: when `true`, uploaded images are converted to a base64 data URI and saved directly in the database instead of being written to disk

### Important notes

- `fileUploads()` must resolve at least one valid attribute or the trait throws an `InvalidArgumentException`.
- If `folder` is empty, the trait falls back to the model table name.
- `as_base64` only applies when the incoming value is an `UploadedFile`.
- In base64 mode, the uploaded file must be an image. Non-image uploads will trigger an `InvalidArgumentException`.

## Supported Input Values

For each configured file attribute, the trait can handle these values:

### 1. `UploadedFile`

```php
$user->image = $request->file('image');
$user->save();
```

Behaviour:
- if `as_base64` is `false`, the file is stored on disk and the database column stores the path
- if `as_base64` is `true`, image contents are converted to a base64 data URI and stored in the column

### 2. URL string

```php
$user->image = 'https://example.com/avatars/default-user.png';
$user->save();
```

Behaviour:
- the URL is stored as-is
- no file is copied into local storage
- replacing a previously stored local file will still delete the old local file

### 3. Base64 image string

```php
$user->image = 'data:image/png;base64,...';
$user->save();
```

Behaviour:
- the value is stored as-is
- it is treated as a valid image source by the helper methods

### 4. `null` or empty string

```php
$user->image = null;
$user->save();
```

Behaviour:
- any previously stored local file is deleted
- the database value is set to `null`

## Storage and Cleanup Lifecycle

The trait hooks into model events:

### On save

For every configured attribute:
- if the attribute is unchanged, nothing happens
- if the new value is `null` or `''`, the old stored file is deleted and the attribute is cleared
- if the new value is an `UploadedFile`, the old stored file is deleted and the new file is processed
- if the new value is a URL or base64 string and differs from the old value, any old stored file is deleted

### On delete

When the model is deleted, the trait removes any locally stored files for configured attributes.

It only deletes values that represent stored filesystem paths. It does not try to delete:
- URLs
- base64 data
- empty values

## Public Helper Methods

These methods are available on any model using the trait.

### `hasFile(?string $attribute = null): bool`

Returns whether the model currently has a usable file value.

Examples:

```php
$user->hasFile();
$user->hasFile('image');
$user->hasFile('image');
```

Behaviour:
- returns `false` for empty values
- returns `true` for valid base64 images
- returns `true` for valid `http` or `https` URLs
- checks storage existence for stored file paths

If no attribute is provided, the first configured attribute is used.

### `fileUrl(?string $attribute = null): ?string`

Returns a URL or data source suitable for views.

Examples:

```php
$user->fileUrl();
$user->fileUrl('image');
$user->fileUrl('image');
```

Behaviour:
- returns `null` when no value exists
- returns the original value unchanged for base64 images
- returns the original value unchanged for external URLs
- returns `Storage::disk(...)->url($path)` for stored file paths

### `fileIsImage(?string $attribute = null): bool`

Returns whether the value should be treated as a displayable image.

Examples:

```php
$user->fileIsImage();
$user->fileIsImage('image');
```

Behaviour:
- returns `true` for base64 image values
- returns `true` when the URL or path has one of these extensions:
  `jpg`, `jpeg`, `png`, `gif`, `webp`, `bmp`, `svg`, `avif`
- returns `false` for non-image values such as PDFs

### `fileName(?string $attribute = null): ?string`

Returns a filename-like label derived from the stored value.

Examples:

```php
$user->fileName();
$user->fileName('image');
```

Behaviour:
- returns `null` for empty values
- returns the basename for stored paths and URLs
- returns a generated name like `file.jpeg` for base64 image data

## Dynamic Accessors

The trait also exposes computed attributes on the model automatically for every configured file attribute.

For an attribute called `image`, these are available:
- `$model->image_url`
- `$model->image_is_image`
- `$model->image_name`
- `$model->image_exists`

These map to:
- `*_url` -> `fileUrl($attribute)`
- `*_is_image` -> `fileIsImage($attribute)`
- `*_name` -> `fileName($attribute)`
- `*_exists` -> `hasFile($attribute)`

Example:

```php
$user->image_url;
$user->image_is_image;
$user->image_name;
$user->image_exists;
```

## Single-Attribute and Multi-Attribute Usage

### Single attribute example

```php
class User extends Authenticatable
{
    use FileUpload;

    protected function fileUploads(): array
    {
        return [
            'image' => ['as_base64' => true],
        ];
    }
}
```

Usage:

```php
$user->image = $request->file('image');
$user->save();

$user->image_url;
$user->image_name;
$user->image_exists;
```

### Multiple attributes example

```php
class StaffMember extends Model
{
    use FileUpload;

    protected function fileUploads(): array
    {
        return [
            'photo' => [
                'disk' => 'public',
                'folder' => 'users/photos',
                'as_base64' => true,
            ],
            'resume' => [
                'disk' => 'public',
                'folder' => 'users/resumes',
                'as_base64' => false,
            ],
        ];
    }
}
```

Usage:

```php
$staff->photo = $request->file('photo');
$staff->resume = $request->file('resume');
$staff->save();

$staff->photo_url;
$staff->photo_is_image;
$staff->resume_url;
$staff->resume_name;
```

## View Usage

### Image or file link

```blade
@if($user->image_exists)
    @if($user->image_is_image)
        <img src="{{ $user->image_url }}" alt="{{ $user->name }}">
    @else
        <a href="{{ $user->image_url }}" download="{{ $user->image_name }}">
            Download {{ $user->image_name }}
        </a>
    @endif
@endif
```

### Using helper methods instead of dynamic attributes

```blade
@if($user->hasFile('image'))
    @if($user->fileIsImage('image'))
        <img src="{{ $user->fileUrl('image') }}" alt="{{ $user->name }}">
    @else
        <a href="{{ $user->fileUrl('image') }}" download="{{ $user->fileName('image') }}">
            Download {{ $user->fileName('image') }}
        </a>
    @endif
@endif
```

## Validation Recommendations

Validate incoming files before assigning them to the model.

Example:

```php
use Illuminate\Validation\Rules\File;

$validated = $request->validate([
    'image' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
]);
```

For base64-enabled image attributes, make sure your validation rules only allow image uploads when receiving `UploadedFile` input.

## Practical Example

The current `User` model is configured like this:

```php
protected function fileUploads(): array
{
    return [
        'image' => ['as_base64' => true],
    ];
}
```

That means:
- uploading a profile image to `image` stores a base64 data URI in the database
- `$user->image_url` returns that same data URI
- `$user->image_name` returns a generated filename such as `file.jpeg`
- `$user->image_exists` returns `true` when the base64 image is present

## Errors and Misconfiguration

The trait will throw an `InvalidArgumentException` when:
- `fileUploads()` returns no usable attributes
- you request a helper method for an attribute that is not configured
- `as_base64` is enabled and an uploaded file is not an image

It may throw a `RuntimeException` if an uploaded file cannot be read while converting it to base64.

## Best Practices

- Use `longText` columns for attributes that may contain base64 image data.
- Keep validation outside the trait in requests or controllers.
- Use path storage for larger files such as PDFs, ZIPs, or media downloads.
- Use base64 only when embedding image data directly in the database is an intentional design choice.
- Use the dynamic accessors or helper methods in views instead of manually checking paths, URLs, or MIME types.
- Configure each file attribute explicitly in `fileUploads()` rather than scattering file-handling logic through controllers.

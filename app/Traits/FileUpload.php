<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait FileUpload
{
    private const DISPLAYABLE_IMAGE_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif',
    ];

    private const DYNAMIC_SUFFIX_URL = '_url';
    private const DYNAMIC_SUFFIX_IS_IMAGE = '_is_image';
    private const DYNAMIC_SUFFIX_NAME = '_name';
    private const DYNAMIC_SUFFIX_EXISTS = '_exists';

    private ?array $resolvedFileUploadConfig = null;

    /**
     * Define one or many file attributes.
     *
     * Example:
     * [
     *   'photo' => ['disk' => 'public', 'folder' => 'staff/photos', 'as_base64' => true],
     *   'resume' => ['disk' => 'public', 'folder' => 'staff/resumes', 'as_base64' => false],
     * ]
     */
    abstract protected function fileUploads(): array;

    protected static function bootFileUpload(): void
    {
        static::saving(function ($model) {
            $model->prepareConfiguredFileAttributesForSave();
        });

        static::deleting(function ($model) {
            foreach ($model->resolveFileUploadConfig() as $attribute => $config) {
                $value = $model->getOriginal($attribute);
                $model->deleteStoredFileUsingConfig($value, $config);
            }
        });
    }

    public function hasFile(?string $attribute = null): bool
    {
        $attribute ??= $this->getDefaultFileAttribute();
        $config = $this->getConfigForAttribute($attribute);
        $value = $this->attributes[$attribute] ?? null;

        if (!filled($value)) {
            return false;
        }

        if ($this->isBase64ImageValue($value) || $this->isUrlValue($value)) {
            return true;
        }

        return Storage::disk($config['disk'])->exists($value);
    }

    public function fileUrl(?string $attribute = null): ?string
    {
        $attribute ??= $this->getDefaultFileAttribute();
        $config = $this->getConfigForAttribute($attribute);
        $value = $this->attributes[$attribute] ?? null;

        if (!$value) {
            return null;
        }

        if ($this->isBase64ImageValue($value) || $this->isUrlValue($value)) {
            return $value;
        }

        return Storage::disk($config['disk'])->url($value);
    }

    public function fileIsImage(?string $attribute = null): bool
    {
        $attribute ??= $this->getDefaultFileAttribute();
        $value = $this->attributes[$attribute] ?? null;

        return $this->isDisplayableImageValue($value);
    }

    public function fileName(?string $attribute = null): ?string
    {
        $attribute ??= $this->getDefaultFileAttribute();
        $value = $this->attributes[$attribute] ?? null;

        return $this->deriveFileNameFromValue($value);
    }

    /**
     * Dynamic accessors for configured attributes:
     * photo_url, photo_is_image, photo_name, photo_exists
     */
    public function getAttribute($key)
    {
        if (is_string($key) && !$this->hasConcreteAttributeOrMutator($key)) {
            $dynamicValue = $this->resolveDynamicFileAccessor($key);
            if ($dynamicValue['matched']) {
                return $dynamicValue['value'];
            }
        }

        return parent::getAttribute($key);
    }

    protected function prepareConfiguredFileAttributesForSave(): void
    {
        foreach ($this->resolveFileUploadConfig() as $attribute => $config) {
            $this->prepareFileAttributeForSaveUsingConfig($attribute, $config);
        }
    }

    protected function prepareFileAttributeForSaveUsingConfig(string $attribute, array $config): void
    {
        if (!$this->isDirty($attribute)) {
            return;
        }

        $value = $this->attributes[$attribute] ?? null;
        $current = $this->getOriginal($attribute);

        if ($value === null || $value === '') {
            $this->deleteStoredFileUsingConfig($current, $config);
            $this->attributes[$attribute] = null;
            return;
        }

        if ($value instanceof UploadedFile) {
            $this->attributes[$attribute] = $this->storeUploadedFileUsingConfig($value, $current, $config);
            return;
        }

        if ($value !== $current) {
            $this->deleteStoredFileUsingConfig($current, $config);
        }

        $this->attributes[$attribute] = $value;
    }

    protected function deleteStoredFileUsingConfig($value, array $config): void
    {
        if (!$this->isStoredFileValue($value)) {
            return;
        }

        Storage::disk($config['disk'])->delete($value);
    }

    protected function storeUploadedFileUsingConfig(UploadedFile $file, $currentValue, array $config): string
    {
        $this->deleteStoredFileUsingConfig($currentValue, $config);

        if ($config['as_base64']) {
            return $this->fileToBase64($file);
        }

        return $file->store($config['folder'], $config['disk']);
    }

    protected function resolveFileUploadConfig(): array
    {
        if ($this->resolvedFileUploadConfig !== null) {
            return $this->resolvedFileUploadConfig;
        }

        $uploads = $this->fileUploads();
        if ($uploads === []) {
            throw new \InvalidArgumentException('FileUpload requires at least one configured attribute via fileUploads().');
        }

        $resolved = [];

        foreach ($uploads as $key => $options) {
            if (is_int($key) && is_string($options)) {
                $attribute = $options;
                $options = [];
            } elseif (is_string($key) && is_array($options)) {
                $attribute = $key;
            } else {
                continue;
            }

            $defaults = [
                'disk' => 'public',
                'folder' => $this->getTable(),
                'as_base64' => false,
            ];

            $config = array_merge($defaults, $options);
            $config['folder'] = ($config['folder'] ?? '') !== '' ? $config['folder'] : $this->getTable();
            $config['as_base64'] = (bool) ($config['as_base64'] ?? false);

            $resolved[$attribute] = $config;
        }

        if ($resolved === []) {
            throw new \InvalidArgumentException('FileUpload could not resolve any valid attribute config from fileUploads().');
        }

        $this->resolvedFileUploadConfig = $resolved;

        return $this->resolvedFileUploadConfig;
    }

    protected function getConfigForAttribute(string $attribute): array
    {
        $config = $this->resolveFileUploadConfig();

        if (!array_key_exists($attribute, $config)) {
            throw new \InvalidArgumentException("FileUpload attribute [{$attribute}] is not configured.");
        }

        return $config[$attribute];
    }

    protected function getDefaultFileAttribute(): string
    {
        return array_key_first($this->resolveFileUploadConfig());
    }

    protected function hasConcreteAttributeOrMutator(string $key): bool
    {
        if (array_key_exists($key, $this->attributes)) {
            return true;
        }

        if ($this->hasGetMutator($key) || $this->hasAttributeMutator($key)) {
            return true;
        }

        if (method_exists($this, $key)) {
            return true;
        }

        return false;
    }

    protected function resolveDynamicFileAccessor(string $key): array
    {
        foreach (array_keys($this->resolveFileUploadConfig()) as $attribute) {
            $suffixes = [
                self::DYNAMIC_SUFFIX_URL => fn () => $this->fileUrl($attribute),
                self::DYNAMIC_SUFFIX_IS_IMAGE => fn () => $this->fileIsImage($attribute),
                self::DYNAMIC_SUFFIX_NAME => fn () => $this->fileName($attribute),
                self::DYNAMIC_SUFFIX_EXISTS => fn () => $this->hasFile($attribute),
            ];

            foreach ($suffixes as $suffix => $resolver) {
                if ($key === $attribute . $suffix) {
                    return ['matched' => true, 'value' => $resolver()];
                }
            }
        }

        return ['matched' => false, 'value' => null];
    }

    protected function isBase64ImageValue($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (!preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $value)) {
            return false;
        }

        $base64 = substr($value, strpos($value, ',') + 1);

        return base64_encode(base64_decode($base64, true)) === $base64;
    }

    protected function isUrlValue($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https'], true);
    }

    protected function fileToBase64(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        if (!is_string($mime) || !str_starts_with($mime, 'image/')) {
            throw new \InvalidArgumentException('Uploaded file is not an image.');
        }

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new \RuntimeException('Failed to read uploaded file contents.');
        }

        return "data:{$mime};base64," . base64_encode($contents);
    }

    protected function extractPathFromFileValue($value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }

        if ($this->isUrlValue($value)) {
            return parse_url($value, PHP_URL_PATH) ?? '';
        }

        return $value;
    }

    protected function isStoredFileValue($value): bool
    {
        return is_string($value)
            && $value !== ''
            && !$this->isBase64ImageValue($value)
            && !$this->isUrlValue($value);
    }

    protected function isDisplayableImageValue($value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        if ($this->isBase64ImageValue($value)) {
            return true;
        }

        $path = $this->extractPathFromFileValue($value);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::DISPLAYABLE_IMAGE_EXTENSIONS, true);
    }

    protected function getBase64ImageMimeType(string $base64): ?string
    {
        if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,/', $base64, $matches)) {
            return null;
        }

        return $matches[1];
    }

    protected function deriveFileNameFromValue($value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        if ($this->isBase64ImageValue($value)) {
            $mime = $this->getBase64ImageMimeType($value);
            $extension = $mime ? explode('/', $mime)[1] : 'png';

            return "file.{$extension}";
        }

        $path = $this->extractPathFromFileValue($value);
        $name = basename($path);

        return $name !== '' ? $name : basename($value);
    }
}

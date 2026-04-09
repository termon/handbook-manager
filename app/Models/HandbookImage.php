<?php

namespace App\Models;

use App\Traits\FileUpload;
use Database\Factories\HandbookImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * @property string $disk
 * @property string $path
 * @property int $id
 * @property int $handbook_id
 * @property string $name
 * @property string|null $alt_text
 * @property string $mime_type
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Handbook $handbook
 *
 * @method static \Database\Factories\HandbookImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage whereAltText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage whereHandbookId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookImage whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class HandbookImage extends Model
{
    /** @use HasFactory<HandbookImageFactory> */
    use FileUpload, HasFactory;

    protected function fileUploads(): array
    {
        return [
            'path' => [
                'as_base64' => true,
                // Use the entries below instead when storing handbook images on disk.
                // 'disk' => 'public',
                // 'folder' => "handbooks/{$this->attributes['handbook_id'] ?? $this->getRawOriginal('handbook_id')}/images",
            ],
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }

    protected $fillable = [
        'handbook_id',
        'disk',
        'path',
        'name',
        'alt_text',
        'mime_type',
        'size',
    ];

    public static function sanitizedUploadName(UploadedFile $file): string
    {
        return (new static)->sanitizeUploadedFileName($file);
    }

    /**
     * @return BelongsTo<Handbook, $this>
     */
    public function handbook(): BelongsTo
    {
        return $this->belongsTo(Handbook::class);
    }

    public function relativeUrl(): string
    {
        return (string) $this->fileUrl('path');
    }

    public function markdownSnippet(): string
    {
        return sprintf('![%s](%s)', $this->alt_text ?? $this->name, $this->name);
    }
}

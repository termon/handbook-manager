<?php

namespace App\Models;

use Database\Factories\HandbookImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $disk
 * @property string $path
 * @property int $id
 * @property int $handbook_id
 * @property string $name
 * @property string|null $alt_text
 * @property string $mime_type
 * @property int $size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Handbook $handbook
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
 * @mixin \Eloquent
 */
class HandbookImage extends Model
{
    /** @use HasFactory<HandbookImageFactory> */
    use HasFactory;

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

    protected static function booted(): void
    {
        static::deleting(function (self $image): void {
            Storage::disk($image->disk)->delete($image->path);
        });
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
        return '/storage/'.ltrim($this->path, '/');
    }

    public function markdownSnippet(): string
    {
        return sprintf('![%s](%s)', $this->alt_text ?? $this->name, $this->name);
    }
}

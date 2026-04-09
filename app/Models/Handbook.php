<?php

namespace App\Models;

use Database\Factories\HandbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HandbookImage> $images
 * @property-read int|null $images_count
 * @property-read \App\Models\User|null $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HandbookPage> $pages
 * @property-read int|null $pages_count
 * @method static \Database\Factories\HandbookFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Handbook whereUserId($value)
 * @mixin \Eloquent
 */
#[Fillable(['user_id', 'title', 'slug', 'description'])]
class Handbook extends Model
{
    /** @use HasFactory<HandbookFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (self $handbook): void {
            $handbook->images()->get()->each->delete();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<HandbookPage, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(HandbookPage::class)->orderBy('position');
    }

    /**
     * @return HasMany<HandbookImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(HandbookImage::class)->latest();
    }
}

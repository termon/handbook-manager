<?php

namespace App\Models;

use Database\Factories\HandbookPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $handbook_id
 * @property string $title
 * @property string $slug
 * @property int $position
 * @property string $body
 * @property bool $is_shareable
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Handbook $handbook
 * @property-read Collection<int, HandbookPagePosition> $positions
 *
 * @method static \Database\Factories\HandbookPageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereHandbookId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereIsShareable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['handbook_id', 'title', 'slug', 'position', 'body', 'is_shareable'])]
class HandbookPage extends Model
{
    /** @use HasFactory<HandbookPageFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (self $page): void {
            $page->positions()->firstOrCreate(
                ['handbook_id' => $page->handbook_id],
                ['position' => $page->position],
            );
        });
    }

    protected function casts(): array
    {
        return [
            'is_shareable' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Handbook, $this>
     */
    public function handbook(): BelongsTo
    {
        return $this->belongsTo(Handbook::class);
    }

    /**
     * @return HasMany<HandbookPagePosition, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(HandbookPagePosition::class);
    }

    public function isEditableIn(Handbook $handbook): bool
    {
        return $this->handbook_id === $handbook->id;
    }

    public function isShared(): bool
    {
        return $this->positions()
            ->where('handbook_id', '!=', $this->handbook_id)
            ->exists();
    }
}

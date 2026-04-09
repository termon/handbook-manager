<?php

namespace App\Models;

use Database\Factories\HandbookPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $handbook_id
 * @property string $title
 * @property string $slug
 * @property int $position
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Handbook $handbook
 * @method static \Database\Factories\HandbookPageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereHandbookId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable(['handbook_id', 'title', 'slug', 'position', 'body'])]
class HandbookPage extends Model
{
    /** @use HasFactory<HandbookPageFactory> */
    use HasFactory;

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
}

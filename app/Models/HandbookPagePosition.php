<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $handbook_id
 * @property int $handbook_page_id
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Handbook $handbook
 * @property-read HandbookPage $page
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPagePosition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPagePosition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPagePosition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPagePosition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPagePosition whereHandbookId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPagePosition whereHandbookPageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPagePosition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPagePosition wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HandbookPagePosition whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['handbook_id', 'handbook_page_id', 'position'])]
class HandbookPagePosition extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<Handbook, $this>
     */
    public function handbook(): BelongsTo
    {
        return $this->belongsTo(Handbook::class);
    }

    /**
     * @return BelongsTo<HandbookPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(HandbookPage::class, 'handbook_page_id');
    }
}

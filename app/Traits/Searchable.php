<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Scope a query to search across one or more attributes.
     *
     * @param  Builder  $query
     * @param  string|null  $term
     * @param  array  $attributes
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $term, array $attributes): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $query->where(function ($q) use ($term, $attributes) {
            foreach ($attributes as $attribute) {
                $q->orWhere($attribute, 'like', '%' . $term . '%');
            }
        });

        return $query;
    }
}

<?php

namespace App\Traits;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * Sortable trait.
 *
 * Based on Github Kyslik/column-sortable
 *
 *  Fixed bug -check existence of column should occur within previous if
 */
trait Sortable
{

    /**
     * @param Builder $query
     * @param $column
     * @param $direction
     *
     * @return Builder
     * @throws \Exception
     */
    public function scopeSortable(Builder $query, $column = 'id', $direction = 'asc'): Builder
    {
        $model = $this;

        if (is_null($column)) {
            return $query;
        }

        // handle relationship column in 'relation.column' format
        $explodeResult = self::explodeSortParameter($column);
        if (! empty($explodeResult)) {
            $relationName = $explodeResult[0];
            $column       = $explodeResult[1];

            // check for existence of relationship
            try {
                $relation = $query->getRelation($relationName);
                $query    = $this->queryJoinBuilder($query, $relation);
            } catch (BadMethodCallException $e) {
                throw new \Exception($relationName, 1, $e);
            } catch (\Exception $e) {
                throw new \Exception("Non-existent relation - {$relationName}", 2, $e);
            }
            $model = $relation->getRelated();
        }


        // check for existence of column
        if (Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $column)) {
            $column = $model->getTable() . '.' . $column;
            $query  = $query->orderBy($column, $direction);
        } else {
            throw new \Exception("Non-existent column - {$relationName}");
        }


        return $query;
    }


    /**
     * @param Builder $query
     * @param BelongsTo|\Illuminate\Database\Eloquent\Relations\HasOne $relation
     *
     * @return Builder
     *
     * @throws \Exception
     */
    private function queryJoinBuilder(Builder $query, $relation): Builder
    {
        $relatedTable = $relation->getRelated()->getTable();
        $parentTable  = $relation->getParent()->getTable();

        if ($parentTable === $relatedTable) {
            $query       = $query->from($parentTable . ' as parent_' . $parentTable);
            $parentTable = 'parent_' . $parentTable;
            $relation->getParent()->setTable($parentTable);
        }

        if ($relation instanceof HasOne) {
            $relatedPrimaryKey = $relation->getQualifiedForeignKeyName();
            $parentPrimaryKey  = $relation->getQualifiedParentKeyName();
        } elseif ($relation instanceof BelongsTo) {
            $relatedPrimaryKey = $relation->getQualifiedOwnerKeyName();
            $parentPrimaryKey  = $relation->getQualifiedForeignKeyName();
        } else {
            throw new \Exception();
        }

        return $this->formJoin($query, $parentTable, $relatedTable, $parentPrimaryKey, $relatedPrimaryKey);
    }

    /**
     * @param $query
     * @param $parentTable
     * @param $relatedTable
     * @param $parentPrimaryKey
     * @param $relatedPrimaryKey
     *
     * @return mixed
     */
    private function formJoin($query, $parentTable, $relatedTable, $parentPrimaryKey, $relatedPrimaryKey)
    {
        $joinType = 'leftJoin';

        return $query->select($parentTable . '.*')->{$joinType}($relatedTable, $parentPrimaryKey, '=', $relatedPrimaryKey);
    }


    /**
     * Explodes parameter if possible and returns array [column, relation]
     * Empty array is returned if explode could not run eg: separator was not found.
     *
     * @param $parameter
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function explodeSortParameter($parameter)
    {
        $separator = '.';

        if (Str::contains($parameter, $separator)) {
            $oneToOneSort = explode($separator, $parameter);
            if (count($oneToOneSort) !== 2) {
                throw new \Exception("Column could not be exploded");
            }

            return $oneToOneSort;
        }

        return [];
    }
}

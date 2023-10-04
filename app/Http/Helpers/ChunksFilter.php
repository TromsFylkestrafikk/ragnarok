<?php

namespace App\Http\Helpers;

use Illuminate\Contracts\Database\Eloquent\Builder;

trait ChunksFilter
{
    /**
     * @param mixed[] $input
     * @return Builder
     */
    protected function applyFilters(array $input, Builder $query)
    {
        $this->applyChunkIdFilter($input, $query);
        return $this->applyStatusFilters($input, $query);
    }

    /**
     * @param array $input
     * @param Builder $query
     *
     * @return Builder
     */
    protected function applyStatusFilters($input, $query)
    {

        foreach (array_filter(array_intersect_key($input, array_flip(['fetch_status', 'import_status']))) as $key => $value) {
            $query->where($key, $value);
        }
        return $query;
    }

    /**
     * Add chunk ID constrains to query
     *
     * @param array $input
     * @param Builder $query
     *
     * @return Builder
     */
    protected function applyChunkIdFilter($input, $query)
    {
        $searchId = $input['chunk_id'] ?? null;
        if ($searchId) {
            return $this->searchIdToQuery($searchId, $query);
        }
        return $query;
    }

    /**
     * Add query filters based on ID input.
     *
     * The input can contain a range, or a pattern.  Ranges are given in format
     * '<id1> .. <id2>' WITH the space between. Searching for ID with a given
     * pattern is performed if no range is found.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    protected function searchIdToQuery(string $search, $query)
    {
        $range = explode(' .. ', $search);
        if (count($range) > 1) {
            sort($range);
            list($fromId, $toId) = $range;
            return $query->where([['chunk_id', '>=', $fromId], ['chunk_id', '<=', $toId]]);
        }
        return $query->where('chunk_id', 'LIKE', "%{$search}%");
    }
}

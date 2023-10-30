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
        $this->applySizeFilter($input, 'fetch_size', $query);
        $this->applySizeFilter($input, 'import_size', $query);
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
     * @param string $search
     * @param Builder $query
     *
     * @return Builder
     */
    protected function searchIdToQuery(string $search, $query)
    {
        $match = [];
        $hits = preg_match_all('/\s*(?<single>[^<>\s]+)|((?<compare>[<>]=?)\s*(?<value>[^<>\s]+))\s*/', $search, $match);
        for ($hit = 0; $hit < $hits; $hit++) {
            if (strlen($match['single'][$hit])) {
                return $query->where('chunk_id', 'LIKE', sprintf('%%%s%%', $match['single'][$hit]));
            }
            $query->where('chunk_id', $match['compare'][$hit], $match['value'][$hit]);
        }
    }

    /**
     * Add range filter using comparison operators.
     *
     * Exact value is specified using a numeric value alone. Comparison is done
     * by using either '<', '>', '>=' or '<=' as prefix to number, with max two
     * comparison groups allowed. Examples of allowed search:
     * - < 300
     * - >= 300 < 500
     * - 200
     *
     * @param array $search
     * @param string $column
     * @param Builder $query
     * @return Builder
     */
    protected function applySizeFilter($input, string $column, $query)
    {
        $search = $input[$column] ?? null;
        if (!$search) {
            return $query;
        }
        $match = [];
        $hits = preg_match_all('/\s*(?<single>\d+)|((?<compare>[<>]=?)\s*(?<value>\d+))\s*/', $search, $match);
        for ($hit = 0; $hit < $hits; $hit++) {
            if (strlen($match['single'][$hit])) {
                $query->where($column, $match['single'][$hit]);
                continue;
            }
            $query->where($column, $match['compare'][$hit], $match['value'][$hit]);
        }
        return $query;
    }
}

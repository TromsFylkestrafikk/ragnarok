<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

trait ChunkFormRules
{
    /**
     * @return mixed[]
     */
    public function filterRules(): array
    {
        $states = ['new', 'in_progress', 'finished', 'failed'];
        // Allow for filters like 'N', '< N', '<N > M', '<= N >= M'
        $sizeRegex = 'regex:/^\s*(\d+|([<>]=?\s*\d+\s*){1,2})\s*$/';
        return [
            'chunk_id' => ['nullable', 'regex:/^\s*([^<>\s]+|([<>]=?\s*[^<>\s]+\s*){1,2})\s*$/'],
            'fetch_status' => ['nullable', Rule::in($states)],
            'fetch_size' => ['nullable', $sizeRegex],
            'import_status' => ['nullable', Rule::in($states)],
            'import_size' => ['nullable', $sizeRegex],
        ];
    }

    /**
     * @param string|array $appendOp Additional allowed operations.
     *
     * @return mixed[]
     */
    public function operationRules($appendOp = []): array
    {
        if (!is_array($appendOp)) {
            $appendOp = [$appendOp];
        }
        return [
            'operation' => [
                'required',
                Rule::in(array_merge($appendOp, ['fetch', 'import', 'deleteFetched', 'deleteImported'])),
            ],
            'forceFetch' => 'boolean',
            'forceImport' => 'boolean',
        ];
    }

    /**
     * @param string|array $appendOp Additional allowed operations.
     *
     * @return mixed[]
     */
    public function operationAndFilterRules($appendOp = [])
    {
        return array_merge($this->filterRules(), $this->operationRules($appendOp));
    }
}

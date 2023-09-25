<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SinkCollection extends ResourceCollection
{
    public static $wrap = 'sinks';

    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->collection->keyBy('id')->map->toArray($request)->all();
    }
}

<?php

namespace App\Http\Resources;

use App\Services\SinkHandler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * \App\Http\Resources\SinkResource
 *
 * @property string $id Unique sink ID
 * @property string $title Title/name of sink for presentation
 * @property string $impl_class Sink implementation of SinkBase
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Chunk> $chunks
 * @property-read int|null $chunks_count
 * @method static \Illuminate\Database\Eloquent\Builder|Sink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sink query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sink whereImplClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sink whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Relations\HasMany chunks()
 * @mixin \Eloquent
 */
class SinkResource extends JsonResource
{
    public static $wrap = 'sink';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection */
        $importCounts = $this->chunks()
            ->select(DB::raw('count(*) as count'), 'import_status')
            ->groupBy('import_status')
            ->get()
            ->keyBy('import_status');

        $cHandler = new SinkHandler($this->resource);
        return array_merge(parent::toArray($request), [
            'chunksCount' => $importCounts->reduce(fn ($result, $chunk) => $result + $chunk->count, 0),
            'chunksNewCount' => $importCounts['new']->count ?? 0,
            'chunksImportedCount' => $importCounts['finished']->count ?? 0,
            'chunksFailedCount' => $importCounts['failed']->count ?? 0,
            'newChunks' => $cHandler->getNewChunks()->count(),
            'lastImportedChunk' => $this->chunks()
                ->whereIn('import_status', ['finished', 'failed'])
                ->orderBy('imported_at', 'desc')
                ->take(1)
                ->first(),
        ]);
    }
}

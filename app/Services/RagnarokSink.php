<?php

namespace App\Services;

use App\Models\SinkImport;
use App\Models\Chunk;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use TromsFylkestrafikk\RagnarokSink\Sinks\SinkBase;
use TromsFylkestrafikk\RagnarokSink\Services\DbBulkInsert;

class RagnarokSink
{
    public function __construct(public SinkBase $src)
    {
        //
    }

    public function lastImport()
    {
        return SinkImport::where('sink_id', $this->src->id)->orderBy('started_at', 'desc')->first();
    }

    /**
     * Get the state of a single sink
     *
     * @return mixed[]
     */
    public function getState(): array
    {
        return [
            'id' => $this->src->id,
            'title' => $this->src->title,
            'fromDate' => $this->src->getFromDate(),
            'toDate' => $this->src->getToDate(),
            'chunksCount' => $this->src->chunksCount(),
        ];
    }

    public function getChunkStatus(string $sinkId, array $chunkIds): Collection
    {
        return Chunk::where('sink_id', $sinkId)
            ->whereIn('id', $chunkIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * @param int $itemsPerPage
     * @return array
     */
    public function getChunks($itemsPerPage = 20, $orderBy = null)
    {
        /** @var \Illuminate\Database\Eloquent\Builder $baseQuery */
        $baseQuery = Chunk::where('sink_id', $this->src->id);
        if (!empty($orderBy)) {
            $baseQuery->orderBy($orderBy['key'], $orderBy['order']);
        }
        $baseQuery->orderBy('chunk_id', 'desc');
        $count = $baseQuery->count();
        if (!$count) {
            $this->initChunks();
        }
        return $baseQuery->paginate($itemsPerPage)->items();
    }

    protected function initChunks()
    {
        $ids = $this->src->getChunkIds();
        $records = [];
        foreach ($ids as $chunkId) {
            $records[] = [
                'chunk_id' => $chunkId,
                'sink_id' => $this->src->id,
            ];
        }
        Chunk::upsert($records, ['chunk_id', 'sink_id']);
    }
}

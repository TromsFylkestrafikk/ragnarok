<?php

namespace App\Services;

use App\Models\SinkImport;
use App\Models\Chunk;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use TromsFylkestrafikk\RagnarokSink\Sinks\SinkBase;
use TromsFylkestrafikk\RagnarokSink\Services\DbBulkInsert;
use TromsFylkestrafikk\RagnarokSink\Traits\LogPrintf;

class RagnarokSink
{
    use LogPrintf;

    public function __construct(public SinkBase $src)
    {
        $this->logPrintfInit("[Sink %s]: ", $src->id);
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
     *
     * @return array
     */
    public function getChunks($itemsPerPage = 20, $orderBy = null)
    {
        /** @var Builder $baseQuery */
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

    /**
     * Stage one retrieval of data from sink.
     *
     * @param array $chunkIds List of chunks to fetch
     *
     * @return $this
     */
    public function fetchChunks($chunkIds): RagnarokSink
    {
        $chunks = Chunk::where('sink_id', $this->src->id)->whereIn('id', $chunkIds)->get();
        if ($chunks->count() !== count($chunkIds)) {
            throw new Exception('Mismatch between requested and actual chunks');
        }
        $start = microtime(true);
        $lapStart = $start;
        $this->debug('Fetching %d chunks ...', $chunks->count());
        foreach ($chunks as $chunk) {
            $chunk->fetch_status = 'in_progress';
            $chunk->imported_at = null;
            $chunk->save();
            $chunk->fetch_status = $this->src->fetch($chunk->chunk_id) ? 'finished' : 'failed';
            $chunk->fetched_at = now();
            $chunk->save();
            $lapTime = microtime(true);
            $this->debug('Fetched chunk %s in %.2f seconds', $chunk->chunk_id, $lapTime - $lapStart);
            $lapStart = $lapTime;
        }
        $this->info("Fetched %d chunks in %.2f seconds", count($chunkIds), microtime(true) - $start);
        return $this;
    }

    /**
     * Remove chunks from stage 1 storage.
     *
     * @param array $chunkIds
     *
     * @return $this
     */
    public function removeChunks($chunkIds): RagnarokSink
    {
        $chunks = Chunk::where('sink_id', $this->src->id)->whereIn('id', $chunkIds)->get();
        $start = microtime(true);
        foreach ($chunks as $chunk) {
            /** @var Chunk $chunk */
            $chunk->fetched_at = null;
            $chunk->fetch_status = 'new';
            $chunk->save();
            $this->src->removeChunk($chunk->chunk_id);
        }
        $this->info('Deleted %d chunks in %.2f seconds', count($chunkIds), microtime(true) - $start);
        return $this;
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

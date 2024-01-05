<?php

namespace App\Http\Resources;

use App\Models\Chunk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * App\Http\Resources\ChunkResource
 *
 * @property int $id Chunk ID
 * @property string $chunk_id Chunk id as given by source
 * @property string $sink_id
 * @property string|null $chunk_date What moment in time this chunk belongs to
 * @property int|null $sink_file_id File assocciated with fetched chunk
 * @property string $fetch_status Raw data retrieval status
 * @property int|null $fetch_size Total size of fetched files/data
 * @property string|null $fetch_message Status/error message of last fetch operation
 * @property string|null $fetch_version Version/checksum of downloaded chunk
 * @property string|null $fetch_batch Batch ID of current fetch operation
 * @property string|null $fetched_at Fetch timestamp
 * @property string $import_status Import status
 * @property int|null $import_size Total number of imported records
 * @property string|null $import_message Status/error message of last import operation
 * @property string|null $import_version Import is based on this fetch version/checksum
 * @property string|null $import_batch Batch ID of current import operation
 * @property string|null $imported_at Import timestamp
 * @property bool $can_delete_fetched
 * @property bool $can_delete_imported
 * @property bool $is_modified
 * @property bool $need_fetch
 * @property bool $need_import
 * @property bool $not_in_batch
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Sink|null $sink
 * @property-read SinkFile|null $sinkFile
 * @method static Builder|Chunk canDeleteFetched()
 * @method static Builder|Chunk canDeleteImported()
 * @method static Builder|Chunk canFetch()
 * @method static Builder|Chunk canImport()
 * @method static Builder|Chunk isModified()
 * @method static Builder|Chunk needFetch()
 * @method static Builder|Chunk needImport()
 * @method static Builder|Chunk newModelQuery()
 * @method static Builder|Chunk newQuery()
 * @method static Builder|Chunk notInBatch()
 * @method static Builder|Chunk query()
 * @method static Builder|Chunk whereChunkDate($value)
 * @method static Builder|Chunk whereChunkId($value)
 * @method static Builder|Chunk whereCreatedAt($value)
 * @method static Builder|Chunk whereFetchBatch($value)
 * @method static Builder|Chunk whereFetchMessage($value)
 * @method static Builder|Chunk whereFetchSize($value)
 * @method static Builder|Chunk whereFetchStatus($value)
 * @method static Builder|Chunk whereFetchVersion($value)
 * @method static Builder|Chunk whereFetchedAt($value)
 * @method static Builder|Chunk whereId($value)
 * @method static Builder|Chunk whereImportBatch($value)
 * @method static Builder|Chunk whereImportMessage($value)
 * @method static Builder|Chunk whereImportSize($value)
 * @method static Builder|Chunk whereImportStatus($value)
 * @method static Builder|Chunk whereImportVersion($value)
 * @method static Builder|Chunk whereImportedAt($value)
 * @method static Builder|Chunk whereSinkFileId($value)
 * @method static Builder|Chunk whereSinkId($value)
 * @method static Builder|Chunk whereUpdatedAt($value)
 */
class ChunkResource extends JsonResource
{
    public static $wrap = 'sink';
}

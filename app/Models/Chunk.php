<?php

namespace App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Chunk
 *
 * @property int $id Chunk ID
 * @property string $chunk_id Chunk id as given by source
 * @property string $sink_id
 * @property int $records Number of records imported
 * @property int|null $import_id Import this chunk belongs to
 * @property string $fetch_status Raw data retrieval status
 * @property string|null $fetched_at Fetch timestamp
 * @property string $import_status Import status
 * @property string|null $imported_at Import timestamp
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SinkImport|null $import
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereChunkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereFetchStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereFetchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereImportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereImportStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereImportedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereRecords($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereSinkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chunk whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Chunk extends Model
{
    use BroadcastsEvents;
    use HasFactory;

    public $incrementing = false;
    protected $table = 'ragnarok_chunks';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'chunk_id',
        'sink_id',
        'records',
        'import_id',
        'fetch_status',
        'fetched_at',
        'import_status',
        'imported_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(SinkImport::class);
    }

    /**
     * @param  string  $event
     * @return PrivateChannel|array
     */
    public function broadcastOn(string $event)
    {
        return match ($event) {
            'updated' => new PrivateChannel('App.Models.Chunk'),
            default => [],
        };
    }
}

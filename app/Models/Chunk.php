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
 * @property string $fetch_status Raw data retrieval status
 * @property int $fetch_size Total size of fetched files/data
 * @property string|null $fetch_message Status/error message of last fetch operation
 * @property string|null $fetched_at Fetch timestamp
 * @property string $import_status Import status
 * @property int $import_size Total number of imported records
 * @property string|null $import_message Status/error message of last import operation
 * @property string|null $imported_at Import timestamp
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
        'fetch_status',
        'fetch_size',
        'fetch_message',
        'fetched_at',
        'import_status',
        'import_size',
        'import_message',
        'imported_at',
    ];

    public function sink(): BelongsTo
    {
        return $this->belongsTo(Sink::class);
    }

    public function broadcastOn(string $event): PrivateChannel|array
    {
        return match ($event) {
            'updated' => new PrivateChannel('App.Models.Chunk'),
            default => [],
        };
    }
}

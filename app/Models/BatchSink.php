<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * \App\Models\BatchSink
 *
 * @property int $id Required for easier eloquent operations
 * @property string $batch_id References job_batches.id
 * @property string $sink_id References ragnarok_sinks.id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Sink|null $sink
 * @method static \Illuminate\Database\Eloquent\Builder|BatchSink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BatchSink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BatchSink query()
 * @method static \Illuminate\Database\Eloquent\Builder|BatchSink whereBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchSink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchSink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchSink whereSinkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchSink whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BatchSink extends Model
{
    public $incrementing = false;
    protected $fillable = ['batch_id', 'sink_id'];
    protected $keyType = 'string';
    protected $table = 'ragnarok_batches';

    public function sink(): BelongsTo
    {
        return $this->belongsTo(Sink::class);
    }
}

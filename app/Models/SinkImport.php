<?php

namespace App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SinkImport
 *
 * @property int $id Sink import ID
 * @property string $sink_name
 * @property string|null $started_at When import from sink started
 * @property string|null $finished_at When import from sink finished
 * @property string $status Import status
 * @method static Builder|SinkImport newModelQuery()
 * @method static Builder|SinkImport newQuery()
 * @method static Builder|SinkImport query()
 * @method static Builder|SinkImport running()
 * @method static Builder|SinkImport whereFinishedAt($value)
 * @method static Builder|SinkImport whereId($value)
 * @method static Builder|SinkImport whereSinkName($value)
 * @method static Builder|SinkImport whereStartedAt($value)
 * @method static Builder|SinkImport whereStatus($value)
 * @mixin \Eloquent
 */
class SinkImport extends Model
{
    use BroadcastsEvents;
    use HasFactory;

    public $timestamps = false;
    protected $table = 'ragnarok_imports';
    protected $fillable = ['sink_name', 'started_at', 'finished_at', 'status'];

    public function scopeRunning(Builder $query): Builder
    {
        return $query->whereIn('status', ['new', 'importing']);
    }

    /**
     * @param  string  $event
     * @return PrivateChannel|array
     */
    public function broadcastOn(string $event)
    {
        return match ($event) {
            'updated' => new PrivateChannel('App.Models.SinkImport'),
            default => [],
        };
    }
}

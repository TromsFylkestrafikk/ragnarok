<?php

namespace App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ragnarok\Sink\Models\SinkFile;

/**
 * \App\Models\Chunk
 *
 * @property int $id Chunk ID
 * @property string $chunk_id Chunk id as given by source
 * @property string $sink_id
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
        'sink_id',
        'chunk_id',
        'sink_file_id',
        'fetch_status',
        'fetch_size',
        'fetch_message',
        'fetch_version',
        'fetched_at',
        'import_status',
        'import_size',
        'import_message',
        'import_version',
        'imported_at',
    ];

    protected $appends = [
        'need_fetch',
        'can_delete_fetched',
        'need_import',
        'can_delete_imported',
        'is_modified',
    ];

    protected $with = ['sinkFile'];

    public function sinkFile(): BelongsTo
    {
        return $this->belongsTo(SinkFile::class);
    }

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

    public function scopeNotInBatch(Builder $query): void
    {
        $query->whereNull('fetch_batch')->whereNull('import_batch');
    }

    /**
     * Query scope ::canFetch()
     *
     * Chunk is in state where fetch is allowed.
     */
    public function scopeCanFetch($query): void
    {
        $query->notInBatch()->whereNot('fetch_status', 'in_progress');
    }

    /**
     * Query scope ::needFetch()
     *
     * Chunk is in state where fetch is allowed.
     */
    public function scopeNeedFetch($query): void
    {
        $query->notInBatch()->whereNotIn('fetch_status', ['in_progress', 'finished']);
    }

    /**
     * Query scope ::canDeleteFetched()
     */
    public function scopeCanDeleteFetched($query): void
    {
        $query->notInBatch()
            ->whereNot('fetch_status', 'new')
            ->whereNot('import_status', 'in_progress')
            ->whereDate('fetched_at', '>', now()->sub(config('ragnarok.delete_age_threshold')));
    }

    /**
     * Query scope ::canImport()
     *
     * Chunk is in a state where import is allowed.
     */
    public function scopeCanImport($query): void
    {
        $query->notInBatch()
            ->whereNot('fetch_status', 'in_progress')
            ->whereNot('import_status', 'in_progress');
    }

    /**
     * Query scope ::needImport()
     *
     * Chunk is in state where import is required to be in sync with upstream
     * data.
     */
    public function scopeNeedImport($query): void
    {
        $query->canImport()
            ->where(function ($query) {
                $query->whereNot('import_status', 'finished')->orWhere->isModified();
            });
    }

    /**
     * Query scope ::canDeleteImported()
     */
    public function scopeCanDeleteImported($query): void
    {
        $query->whereNot('import_status', 'new');
    }

    /**
     * Query scope ::isModified()
     *
     * Chunk is fetched and imported, but the imported version differ.
     */
    public function scopeIsModified($query): void
    {
        $query->where('fetch_status', 'finished')
            ->where('import_status', 'finished')
            ->whereColumn('fetch_version', '<>', 'import_version');
    }

    /**
     * Attribute ->not_in_batch
     */
    protected function notInBatch(): Attribute
    {
        return Attribute::get(
            fn (mixed $val, array $attr = []) => empty($attr['fetch_batch']) && empty($attr['import_batch'])
        );
    }

    /**
     * Attribute ->need_fetch
     */
    protected function needFetch(): Attribute
    {
        return Attribute::get(
            fn (mixed $val, array $attr = []) => $this->not_in_batch
                && !in_array($attr['fetch_status'], array('in_progress', 'finished'))
        );
    }

    /**
     * Attribute ->can_delete_fetched
     */
    protected function canDeleteFetched(): Attribute
    {
        return Attribute::get(
            fn (mixed $val, array $attr = []) => $this->not_in_batch
                && !in_array($attr['fetch_status'], array('in_progress', 'new'))
                && $attr['import_status'] !== 'in_progress'
                && (!$this->fetched_at ||
                    now()->sub(config('ragnarok.delete_age_threshold'))->isBefore($this->fetched_at))
        );
    }

    /**
     * Attribute ->need_import
     */
    protected function needImport(): Attribute
    {
        return Attribute::get(
            fn (mixed $val, array $attr = []) =>
                $this->not_in_batch
                && (($attr['fetch_status'] !== 'in_progress'
                     && !in_array($attr['import_status'], ['in_progress', 'finished']))
                    || $this->is_modified)
        );
    }

    /**
     * Attribute ->can_delete_imported
     */
    protected function canDeleteImported(): Attribute
    {
        return Attribute::get(
            fn (mixed $val, array $attr = []) =>
                $this->not_in_batch
                && !in_array($attr['import_status'], ['in_progress', 'new'])
        );
    }

    /**
     * Attribute ->is_modified
     */
    protected function isModified(): Attribute
    {
        return Attribute::get(
            fn (mixed $val, array $attr = []) =>
                $attr['fetch_status'] === 'finished'
                && $attr['import_status'] === 'finished'
                && $attr['fetch_version'] !== $attr['import_version']
        );
    }
}

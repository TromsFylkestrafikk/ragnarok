<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * \App\Models\Sink
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
 * @method static \Illuminate\Database\Eloquent\Builder|Sink whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sink whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Sink extends Model
{
    public $incrementing = false;
    protected $fillable = ['id', 'title', 'impl_class'];
    protected $table = 'ragnarok_sinks';
    protected $keyType = 'string';

    public function chunks(): HasMany
    {
        return $this->hasMany(Chunk::class);
    }
}

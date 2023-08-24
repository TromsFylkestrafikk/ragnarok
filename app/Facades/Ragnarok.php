<?php

namespace App\Facades;

use App\Services\RagnarokSinks;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection getSinks()
 * @method static \App\Services\RagnarokSink getSink(string $sinkId)
 * @method static \Illuminate\Support\Collection getSinksJson()
 */
class Ragnarok extends Facade
{
    public static function getFacadeAccessor()
    {
        return RagnarokSinks::class;
    }
}

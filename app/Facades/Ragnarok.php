<?php

namespace App\Facades;

use App\Services\RagnarokSinks;
use Illuminate\Support\Facades\Facade;

class Ragnarok extends Facade
{
    public static function getFacadeAccessor()
    {
        return RagnarokSinks::class;
    }
}

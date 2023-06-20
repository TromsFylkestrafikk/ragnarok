<?php

namespace App\Services;

use TromsFylkestrafikk\RagnarokSink\Sinks\SinkBase;
use App\Models\SinkImport;

class RagnarokSink
{
    public function __construct(public SinkBase $src)
    {
        //
    }

    public function lastImport()
    {
        return SinkImport::where('sink_name', $this->src->name)->orderBy('started_at', 'desc')->first();
    }
}

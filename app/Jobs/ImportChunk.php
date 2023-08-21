<?php

namespace App\Jobs;

use App\Facades\Ragnarok;
use App\Models\SinkImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportChunk implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param SinkImport $import
     */
    public function __construct(public SinkImport $import)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->import->status = 'importing';
        $this->import->started_at = now();
        $this->import->save();
        $result = Ragnarok::getSink($this->import->sink_id)->src->import();
        $this->import->status = $result ? 'finished' : 'failed';
        $this->import->finished_at = now();
        $this->import->save();
    }
}

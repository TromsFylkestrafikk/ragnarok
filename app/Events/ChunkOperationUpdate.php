<?php

namespace App\Events;

use Illuminate\Bus\Batch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emitted in various steps of chunk operation batches.
 */
class ChunkOperationUpdate implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var mixed[]
     */
    public $batch;

    /**
     * Create a new event instance.
     *
     * @param string $sinkId
     * @param Batch $batchInstance
     */
    public function __construct(public string $sinkId, Batch $batchInstance)
    {
        $this->batch = $batchInstance->toArray();
        $this->batch['sink_id'] = $sinkId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sinks'),
        ];
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ServiceProvision;

class ProvisionFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $provision;

    public function __construct(ServiceProvision $provision)
    {
        $this->provision = $provision;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

<?php

namespace EllisSystems\Payfast\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookHandled
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  array  $payload
     * @return void
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}

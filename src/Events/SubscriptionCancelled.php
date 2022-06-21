<?php

namespace EllisSystems\Payfast\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use EllisSystems\Payfast\Subscription;

class SubscriptionCancelled
{
    use Dispatchable, SerializesModels;

    /**
     * The subscription instance.
     *
     * @var \EllisSystems\Payfast\Subscription
     */
    public $subscription;

    /**
     * The webhook payload.
     *
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  \EllisSystems\Payfast\Subscription  $subscription
     * @param  array  $payload
     * @return void
     */
    public function __construct(Subscription $subscription, array $payload)
    {
        $this->subscription = $subscription;
        $this->payload = $payload;
    }
}

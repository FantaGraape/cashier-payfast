<?php

namespace EllisSystems\Payfast\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use EllisSystems\Payfast\Order;

class OrderCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The billable entity.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $billable;

    /**
     * The order instance.
     *
     * @var \EllisSystems\Payfast\order
     */
    public $order;

    /**
     * The payload array.
     *
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \EllisSystems\Payfast\Order  $order
     * @param  array  $payload
     * @return void
     */
    public function __construct(Model $billable, Order $order, array $payload)
    {
        $this->billable = $billable;
        $this->subscription = $order;
        $this->payload = $payload;
    }
}

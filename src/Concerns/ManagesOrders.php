<?php

namespace EllisSystems\Payfast\Concerns;

use EllisSystems\Payfast\Cashier;
use EllisSystems\Payfast\Order;
use EllisSystems\Payfast\OrderBuilder;

trait ManagesOrders
{
    /**
     * Begin creating a new order.
     *
     * @param  string  $name
     * @param  int  $plan
     * @return \EllisSystems\Payfast\OrderBuilder;
     */
    public function newOrder($amount, $requestIp)
    {
        return new OrderBuilder($this, $amount, $requestIp);
    }

    /**
     * Get all of the orders for the Billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function orders()
    {
        return $this->morphMany(Cashier::$orderModel, 'billable')->orderByDesc('created_at');
    }

    /**
     * Get an order instance by id.
     *
     * @param  string  $name
     * @return \EllisSystems\Payfast\Order|null
     */
    public function order($id)
    {
        return $this->orders->find($id)->first();
    }
}

<?php

namespace Laravel\Paddle\Concerns;

use Laravel\Paddle\Cashier;
use Laravel\Paddle\Order;
use Laravel\Paddle\OrderBuilder;

trait ManagesOrders
{
    /**
     * Begin creating a new order.
     *
     * @param  string  $name
     * @param  int  $plan
     * @return \Laravel\Paddle\OrderBuilder;
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
     * @return \Laravel\Paddle\Order|null
     */
    public function order($id)
    {
        return $this->orders->find($id)->first();
    }
}

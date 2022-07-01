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
     * @param  int  $amount
     * @param  string  $name
     * @param  string  $requestIp
     * @return \EllisSystems\Payfast\OrderBuilder;
     */
    public function newOrder($name, $amount, $requestIp)
    {
        return new OrderBuilder($this, $name, $amount, $requestIp);
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

<?php

namespace EllisSystems\Payfast\Concerns;

use EllisSystems\Payfast\Cashier;
use EllisSystems\Payfast\Order;

trait ManagesOrders
{
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
     * Begin creating a new order.
     *
     * @param  string  $name
     * @param  int  $plan
     * @return \EllisSystems\Payfast\SubscriptionBuilder
     */
    public function newOrder($name, $plan)
    {
        return new Order($this, $name, $plan);
    }
}

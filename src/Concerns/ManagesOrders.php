<?php

namespace EllisSystems\Payfast\Concerns;

use EllisSystems\Payfast\Cashier;

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
}

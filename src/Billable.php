<?php

namespace Laravel\Paddle;

use Laravel\Paddle\Concerns\ManagesCustomer;
use Laravel\Paddle\Concerns\ManagesReceipts;
use Laravel\Paddle\Concerns\ManagesSubscriptions;
use Laravel\Paddle\Concerns\PerformsCharges;
use Laravel\Paddle\Concerns\ManagesOrders;

trait Billable
{
    use ManagesCustomer;
    use ManagesSubscriptions;
    use ManagesReceipts;
    use PerformsCharges;
    use ManagesOrders;

    /**
     * Get the default Paddle API options for the current Billable model.
     *
     * @param  array  $options
     * @return array
     */
    public function paddleOptions(array $options = [])
    {
        return Cashier::paddleOptions($options);
    }
}

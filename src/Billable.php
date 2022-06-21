<?php

namespace EllisSystems\Payfast;

use EllisSystems\Payfast\Concerns\ManagesCustomer;
use EllisSystems\Payfast\Concerns\ManagesReceipts;
use EllisSystems\Payfast\Concerns\ManagesSubscriptions;
use EllisSystems\Payfast\Concerns\PerformsCharges;

trait Billable
{
    use ManagesCustomer;
    use ManagesSubscriptions;
    use ManagesReceipts;
    use PerformsCharges;

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

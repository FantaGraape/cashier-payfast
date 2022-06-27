<?php

namespace EllisSystems\Payfast;

use EllisSystems\Payfast\Concerns\ManagesCustomer;
use EllisSystems\Payfast\Concerns\ManagesReceipts;
use EllisSystems\Payfast\Concerns\ManagesOrders;
use EllisSystems\Payfast\Concerns\ManagesSubscriptions;
use EllisSystems\Payfast\Concerns\PerformsCharges;

trait Billable
{
    use ManagesCustomer;
    use ManagesOrders;
    use ManagesSubscriptions;
    use ManagesReceipts;
    use PerformsCharges;

    /**
     * Get the default payfast API options for the current Billable model.
     *
     * @param  array  $options
     * @return array
     */
    public function payfastOptions(array $options = [])
    {
        return Cashier::payfastOptions($options);
    }
}

<?php

namespace EllisSystems\Payfast\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use EllisSystems\Payfast\Receipt;

class SubscriptionPaymentSucceeded
{
    use Dispatchable, SerializesModels;

    /**
     * The billable entity.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $billable;

    /**
     * The receipt instance.
     *
     * @var \EllisSystems\Payfast\Receipt
     */
    public $receipt;

    /**
     * The webhook payload.
     *
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \EllisSystems\Payfast\Receipt  $receipt
     * @param  array  $payload
     * @return void
     */
    public function __construct(Model $billable, Receipt $receipt, array $payload)
    {
        $this->billable = $billable;
        $this->receipt = $receipt;
        $this->payload = $payload;
    }

    /**
     * Indicates whether it is the customer’s first payment for this subscription.
     *
     * @return bool
     */
    public function isInitialPayment()
    {
        return ((int) $this->payload['initial_payment']) === 1;
    }
}

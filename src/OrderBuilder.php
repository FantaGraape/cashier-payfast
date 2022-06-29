<?php

namespace Laravel\Paddle;

use Spatie\Url\Url;
use Laravel\Paddle\Cashier;

class OrderBuilder
{
    /**
     * The Billable model that is ordering.
     *
     * @var \Laravel\Paddle\Billable
     */
    protected $billable;

    /**
     * The IP of the order request.
     *
     * @var string
     */
    protected $requestIp;

    /**
     * The amount of the checkout.
     *
     * @var string
     */
    protected $amount;

    /**
     * The first name of the customer.
     *
     * @var string
     */
    protected $firstName;

    /**
     * The last name of the customer.
     *
     * @var string
     */
    protected $lastName;

    /**
     * The email address of the customer.
     *
     * @var string
     */
    protected $emailAddress;

    /**
     * The order id to be sent back on webhook.
     *
     * @var string
     */
    protected $m_payment_id;

    /**
     * The uuid for the transaction.
     *
     * @var string
     */
    protected $uuid;

    /**
     * Create a new order builder instance.
     *
     * @param  \Laravel\Paddle\Billable  $billable
     * @param  string  $amount
     * @param  string  $requestIp
     * @return void
     */
    public function __construct($billable, $amount, $requestIp)
    {
        $this->amount = $amount;
        $this->requestIp = $requestIp;
        $this->billable = $billable;
    }

    /**
     * The coupon to apply to a new order.
     *
     * @param  string  $coupon
     * @return $this
     */
    public function withCoupon($coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * The first name of the customer.
     *
     * @param  string  $coupon
     * @return $this
     */
    public function withFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * The last name of the customer.
     *
     * @param  string  $coupon
     * @return $this
     */
    public function withLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }


    /**
     * The email address of the customer.
     *
     * @param  string  $coupon
     * @return $this
     */
    public function withEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * A seperate notify URL if needed for this order.
     *
     * @param  string  $coupon
     * @return $this
     */
    public function withNotifyURL($notifyURL)
    {
        $this->notifyURL = $notifyURL;

        return $this;
    }


    /**
     * The metadata to apply to a new order.
     *
     * @param  array  $metadata
     * @return $this
     */
    public function withMetadata(array $metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * The return url which will be triggered upon starting the subscription.
     *
     * @param  string  $returnTo
     * @param  string  $checkoutParameter
     * @return $this
     */
    /*  public function returnTo($returnTo, $checkoutParameter = 'checkout')
    {
        $this->returnTo = (string) Url::fromString($returnTo)
            ->withQueryParameter($checkoutParameter, '{checkout_hash}');

        return $this;
    } */

    /**
     * Generate an order for payment processing.
     *
     * @param  array  $options
     * @return string
     */
    public function create(array $options = [])
    {
        $order = $this->billable->orders()->create([
            'billable_id' => $this->billable->getKey(),
            'billable_type' => $this->billable->getMorphClass(),
            'checkout_total' => $this->amount,
            'ip_address' => $this->requestIP,
        ]);
        $this->m_payment_id = $order->id;
        $payload = $this->buildPayload();
        $payload['custom_str2'] = $options;
        $payload['custom_str1'] = array_merge($this->metadata, [
            'subscription_name' => $this->name,
        ]);

        return Cashier::payfastPaymentApi()->onsite->generatepaymentIdentifier($payload);
    }

    /**
     * Build the payload for subscription creation.
     *
     * @return array
     */
    protected function buildPayload()
    {
        return [
            'm_payment_id' => $this->m_payment_id,
            'item_name' => $this->m_payment_id, //WIP
            'name_last' => $this->lastName,
            'name_first' => $this->firstName,
            'email_address' => $this->emailAddress,
            'notify_url' => $this->notifyURL ? $this->notifyURL : config('cashier.notify_url'),
        ];
    }
}

<?php

namespace EllisSystems\Payfast;

use Spatie\Url\Url;
use EllisSystems\Payfast\Cashier;

class OrderBuilder
{
    /**
     * The Billable model that is ordering.
     *
     * @var \EllisSystems\Payfast\Billable
     */
    protected $billable;

    /**
     * The metadata to apply to the subscription.
     *
     * @var array
     */
    protected $metadata = [];

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
     * The notify url.
     *
     * @var string
     */
    protected $notifyURL;

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
     * @param  \EllisSystems\Payfast\Billable  $billable
     * @param  string  $amount
     * @param  string  $requestIp
     * @return void
     */
    public function __construct($billable, $name, $amount, $requestIp)
    {
        $this->name = $name;
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
            'ip_address' => $this->requestIp,
        ]);
        $this->m_payment_id = $order->id;
        $payload = $this->buildPayload();
        $payload['custom_str1'] = array_merge($this->metadata, [
            'subscription_name' => $this->name,
        ]);
        $payload['custom_str2'] = $options;

        return $this->billable->generatePaymentUUID($payload);
    }

    /**
     * Build the payload for subscription creation.
     *
     * @return array
     */
    protected function buildPayload()
    {
        return [
            'notify_url' => $this->notifyURL ? $this->notifyURL : config('cashier.notify_url'),
            'name_first' => $this->firstName,
            'name_last' => $this->lastName,
            'email_address' => $this->emailAddress,
            'm_payment_id' => strval($this->m_payment_id),
            'amount' => number_format(sprintf('%.2f', $this->amount), 2, '.', ''),
            'item_name' => strval($this->m_payment_id), //WIP
        ];
    }
}

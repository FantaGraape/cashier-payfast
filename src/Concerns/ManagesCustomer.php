<?php

namespace EllisSystems\Payfast\Concerns;

use EllisSystems\Payfast\Cashier;

trait ManagesCustomer
{
    /**
     * Create a customer record for the billable model.
     *
     * @param  array  $attributes
     * @return \EllisSystems\Payfast\Customer
     */
    public function createAsCustomer(array $attributes = [])
    {
        return $this->customer()->create($attributes);
    }

    /**
     * Get the customer related to the billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function customer()
    {
        return $this->morphOne(Cashier::$customerModel, 'billable');
    }

    /**
     * Get prices for a set of product ids for this billable model.
     *
     * @param  array|int  $products
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */

    /**
     * Get the billable model's email address to associate with Payfast.
     *
     * @return string|null
     */
    public function payfastEmail()
    {
        return $this->email;
    }

    /**
     * Get the billable model's country to associate with Paddle.
     *
     * This needs to be a 2 letter code. See the link below for supported countries.
     *
     * @return string|null
     *
     * @link https://developer.paddle.com/reference/platform-parameters/supported-countries
     */
    /* PENDING REMOVAL */
    public function payfastCountry()
    {
        //
    }

    /**
     * Get the billable model's postcode to associate with Paddle.
     *
     * See the link below for countries which require this.
     *
     * @return string|null
     *
     * @link https://developer.paddle.com/reference/platform-parameters/supported-countries#countries-requiring-postcode
     */
    /* PENDING REMOVAL */
    public function payfastPostcode()
    {
        //
    }
}

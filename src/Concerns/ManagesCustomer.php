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
    public function productPrices($products, array $options = [])
    {
        $options = array_merge([
            'customer_country' => $this->paddleCountry(),
        ], $options);

        return Cashier::productPrices($products, $options);
    }

    /**
     * Get the billable model's email address to associate with Payfast.
     *
     * @return string|null
     */
    public function email()
    {
        return $this->email;
    }

    /**
     * Get the billable model's last name to associate with Payfast.
     *
     * @return string|null
     */
    public function lastName()
    {
        return $this->lastName;
    }

    /**
     * Get the billable model's first name to associate with Payfast.
     *
     * @return string|null
     */
    public function firstName()
    {
        return $this->firstName;
    }

}

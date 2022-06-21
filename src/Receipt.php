<?php

namespace EllisSystems\Payfast;

use Illuminate\Database\Eloquent\Model;
use Money\Currency;

class Receipt extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'integer',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the billable model related to the receipt.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function billable()
    {
        return $this->morphTo();
    }

    /**
     * Get the subscription related to the receipt.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription()
    {
        return $this->belongsTo(Cashier::$subscriptionModel, 'payfast_token', 'payfast_token');
    }

    /**
     * Get the total amount that was paid.
     *
     * @return string
     */
    public function amount_gross()
    {
        return $this->formatAmount((int) ($this->amount_gross * 100));
    }

     /**
     * Get the transaction fee that was paid.
     *
     * @return string
     */
    public function amount_fee()
    {
        return $this->formatAmount((int) ($this->amount_fee * 100));
    }

    /**
     * Get the net amount after transaction fee that was paid.
     *
     * @return string
     */
    public function amount_net()
    {
        return $this->formatAmount((int) ($this->amount_net * 100));
    }

    /**
     * Get the total tax that was paid.
     *
     * @return string
     */
    public function tax()
    {
        return $this->formatAmount((int) ($this->tax * 100));
    }

    /**
     * Get the used currency for the receipt.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->currency);
    }

    /**
     * Format the given amount_gross into a displayable currency.
     *
     * @param  int  $amount_gross
     * @return string
     */
    protected function formatAmountGross($amount_gross)
    {
        return Cashier::formatAmount($amount_gross, $this->currency);
    }
    /**
     * Format the given amount_fee into a displayable currency.
     *
     * @param  int  $amount_fee
     * @return string
     */
    protected function formatAmountFee($amount_fee)
    {
        return Cashier::formatAmount($amount_fee, $this->currency);
    }
    /**
     * Format the given amount_net into a displayable currency.
     *
     * @param  int  $amount_net
     * @return string
     */
    protected function formatAmountNet($amount_net)
    {
        return Cashier::formatAmount($amount_net, $this->currency);
    }
}

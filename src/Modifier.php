<?php

namespace EllisSystems\Payfast;

use Money\Currency;

class Modifier
{
    /**
     * The Subscription model the modifier belongs to.
     *
     * @var \EllisSystems\Payfast\Subscription
     */
    protected $subscription;

    /**
     * The raw modifier array as returned by Paddle.
     *
     * @var array
     */
    protected $modifier;

    /**
     * Create a new modifier instance.
     *
     * @param  \EllisSystems\Payfast\Subscription  $subscription
     * @param  array  $modifier
     * @return void
     */
    public function __construct(Subscription $subscription, array $modifier)
    {
        $this->subscription = $subscription;
        $this->modifier = $modifier;
    }

    /**
     * Get the modifier's Paddle ID.
     *
     * @return int
     */
    public function id()
    {
        return $this->modifier['modifier_id'];
    }

    /**
     * Get the related subscription.
     *
     * @return \EllisSystems\Payfast\Subscription
     */
    public function subscription()
    {
        return $this->subscription;
    }

    /**
     * Get the total amount.
     *
     * @return string
     */
    public function amount()
    {
        return $this->formatAmount((int) ($this->rawAmount() * 100));
    }

    /**
     * Get the raw total amount.
     *
     * @return float
     */
    public function rawAmount()
    {
        return $this->modifier['amount'];
    }

    /**
     * Get the currency.
     *
     * @return \Money\Currency
     */
    public function currency(): Currency
    {
        return new Currency($this->modifier['currency']);
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return Cashier::formatAmount($amount, $this->currency());
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function description()
    {
        return $this->modifier['description'];
    }

    /**
     * Indicates whether the modifier is recurring.
     *
     * @return bool
     */
    public function recurring()
    {
        return (bool) $this->modifier['is_recurring'];
    }

    /**
     * Deletes itself on Paddle.
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function delete()
    {
        $payload = $this->subscription->billable->paddleOptions([
            'modifier_id' => $this->id(),
        ]);

        return Cashier::post('/subscription/modifiers/delete', $payload);
    }
}

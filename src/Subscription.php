<?php

namespace EllisSystems\Payfast;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use EllisSystems\Payfast\Concerns\Prorates;
use LogicException;
use PayFast\PayFastApi;

/**
 * @property \EllisSystems\Payfast\Billable $billable
 */
class Subscription extends Model
{
    use Prorates;

    const STATUS_ACTIVE = 'active';
    const STATUS_TRIALING = 'trialing';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_PAUSED = 'paused';
    const STATUS_DELETED = 'deleted';
    const STATUS_PENDING_DELETE = 'pending_delete';



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
        'payfast_token' => 'string',
        'frequency' => 'integer',
        'subscription_plan' => 'integer',
        'payment_method' => 'string',
        'quantity' => 'integer',
        'trial_ends_at' => 'datetime:Y-m-d',
        'next_cycle' => 'datetime:Y-m-d',
        'paused_from' => 'datetime:Y-m-d',
        'paused_to' => 'datetime:Y-m-d',
        'ends_at' => 'datetime:Y-m-d',
        'cancelled_at' => 'datetime:Y-m-d',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d h:i:s'
    ];

    /**
     * The cached Paddle info for the subscription.
     *
     * @var array
     */
    protected $payfastInfo;

    public function api()
    {
        $api = new PayFastApi(
            [
                'merchantId' => config('cashier.merchant_id'),
                'passPhrase' => config('cashier.passphrase'),
                'testMode' => config('cashier.sandbox')
            ]
        );
        return $api;
    }

    /**
     * Get the billable model related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function billable()
    {
        return $this->morphTo();
    }

    /**
     * Get all of the receipts for the Billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receipts()
    {
        return $this->hasMany(Cashier::$receiptModel, 'payfast_token', 'payfast_token')->orderByDesc('created_at');
    }

    public function order()
    {
        return $this->hasOne(Cashier::$orderModel);
    }

    /**
     * Determine if the subscription has a specific plan.
     *
     * @param  int  $plan
     * @return bool
     */
    public function hasPlan($plan)
    {
        return $this->subscription_plan == $plan;
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onPausedGracePeriod() || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return (is_null($this->ends_at) || $this->onGracePeriod() || $this->onPausedGracePeriod()) &&
            (!Cashier::$deactivatePastDue || $this->payfast_status !== self::STATUS_PAST_DUE) &&
            $this->payfast_status !== self::STATUS_PAUSED;
    }

    /**
     * Filter query by active.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeActive($query)
    {
        $query->where(function ($query) {
            $query->whereNull('ends_at')
                ->orWhere(function ($query) {
                    $query->onGracePeriod();
                })
                ->orWhere(function ($query) {
                    $query->onPausedGracePeriod();
                });
        })->where('payfast_status', '!=', self::STATUS_PAUSED);

        if (Cashier::$deactivatePastDue) {
            $query->where('payfast_status', '!=', self::STATUS_PAST_DUE);
        }
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function pastDue()
    {
        return $this->payfast_status === self::STATUS_PAST_DUE;
    }

    /**
     * Filter query by past due.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopePastDue($query)
    {
        $query->where('payfast_status', self::STATUS_PAST_DUE);
    }

    /**
     * Determine if the subscription is recurring and not on trial.
     *
     * @return bool
     */
    public function recurring()
    {
        return !$this->onTrial() && !$this->paused() && !$this->onPausedGracePeriod() && !$this->cancelled();
    }

    /**
     * Filter query by recurring.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeRecurring($query)
    {
        $query->notOnTrial()->notCancelled();
    }

    /**
     * Determine if the subscription is paused.
     *
     * @return bool
     */
    public function paused()
    {
        return $this->payfast_status === self::STATUS_PAUSED;
    }

    /**
     * Filter query by paused.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopePaused($query)
    {
        $query->where('payfast_status', self::STATUS_PAUSED);
    }

    /**
     * Filter query by not paused.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotPaused($query)
    {
        $query->where('payfast_status', '!=', self::STATUS_PAUSED);
    }

    /**
     * Determine if the subscription is within its grace period after being paused.
     *
     * @return bool
     */
    public function onPausedGracePeriod()
    {
        return $this->paused_from && $this->paused_from->isFuture();
    }

    /**
     * Filter query by on trial grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnPausedGracePeriod($query)
    {
        $query->whereNotNull('paused_from')->where('paused_from', '>', Carbon::now());
    }

    /**
     * Filter query by not on trial grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnPausedGracePeriod($query)
    {
        $query->whereNull('paused_from')->orWhere('paused_from', '<=', Carbon::now());
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function cancelled()
    {
        return !is_null($this->ends_at);
    }

    /**
     * Filter query by cancelled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeCancelled($query)
    {
        $query->whereNotNull('ends_at');
    }

    /**
     * Filter query by not cancelled.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotCancelled($query)
    {
        $query->whereNull('ends_at');
    }

    /**
     * Determine if the subscription has ended and the grace period has expired.
     *
     * @return bool
     */
    public function ended()
    {
        return $this->cancelled() && !$this->onGracePeriod();
    }

    /**
     * Filter query by ended.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeEnded($query)
    {
        $query->cancelled()->notOnGracePeriod();
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * @return bool
     */
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Filter query by on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', Carbon::now());
    }

    /**
     * Determine if the subscription's trial has expired.
     *
     * @return bool
     */
    public function hasExpiredTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Filter query by expired trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeExpiredTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', Carbon::now());
    }

    /**
     * Filter query by not on trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnTrial($query)
    {
        $query->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', Carbon::now());
    }

    /**
     * Determine if the subscription is within its grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Filter query by on grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnGracePeriod($query)
    {
        $query->whereNotNull('ends_at')->where('ends_at', '>', Carbon::now());
    }

    /**
     * Filter query by not on grace period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotOnGracePeriod($query)
    {
        $query->whereNull('ends_at')->orWhere('ends_at', '<=', Carbon::now());
    }

    /**
     * Perform a "one off" charge on top of the subscription for the given amount.
     *
     * @param  float  $amount
     * @param  string  $name
     * @return array
     *
     * @throws \Exception
     */
    public function charge($amount, $name)
    {

        if (strlen($name) > 100) {
            throw new Exception('Item name has a maximum length of 100 characters.');
        }

        $this->payfastInfo = null;

        return $this->api->subscriptions->adhoc($this->payfast_token, ['amount' => $amount, 'item_name' => $name]);
    }

    /**
     * Increment the quantity of the subscription.
     *
     * @param  int  $count
     * @return $this
     */
    public function incrementQuantity($count = 1)
    {
        /* $this->updateQuantity($this->quantity + $count);

        return $this; */
    }

    /**
     *  Increment the quantity of the subscription, and invoice immediately.
     *
     * @param  int  $count
     * @return $this
     */
    public function incrementAndInvoice($count = 1)
    {
        /* $this->updateQuantity($this->quantity + $count, [
            'bill_immediately' => true,
        ]);

        return $this; */
    }

    /**
     * Decrement the quantity of the subscription.
     *
     * @param  int  $count
     * @return $this
     */
    public function decrementQuantity($count = 1)
    {
        /* return $this->updateQuantity(max(1, $this->quantity - $count)); */
    }

    /**
     * Update the quantity of the subscription.
     *
     * @param  int  $quantity
     * @param  array  $options
     * @return $this
     */
    public function updateQuantity($quantity, array $options = [])
    {
        /* $this->guardAgainstUpdates('update quantities');

        $this->updatePaddleSubscription(array_merge($options, [
            'quantity' => $quantity,
            'prorate' => $this->prorate,
        ]));

        $this->forceFill([
            'quantity' => $quantity,
        ])->save();

        $this->paddleInfo = null;

        return $this; */
    }

    /**
     * Pause the subscription to a specific date. Only supports Monthly frequency at the moment.
     * @param  \DateTimeInterface  $pauseDate
     * @return $this
     */
    public function pause($pauseDate)
    {
        $cycles = $this->$this->payfastInfo()['cycles'];
        $completedCycles = $this->$this->payfastInfo()['cycles_complete'];
        $remainingCycles = $cycles - $completedCycles;
        $pauseDate = Carbon::parse($pauseDate);
        $now = Carbon::createFromFormat('Y-m-d', Carbon::now(), 'UTC')->addMonths($remainingCycles);
        $cycleDifference = $now->diffInMonths($pauseDate);
        $this->api->subscriptions->pause($this->payfast_token, ['cycles' => $cycleDifference]);
        $info = $this->payfastInfo();

        $this->forceFill([
            'payfast_status' => self::STATUS_PAUSED,
            'paused_from' => Carbon::now(),
            'paused_to' => Carbon::now()->addMonths($cycleDifference),
        ])->save();

        $this->payfastInfo = null;

        return $this;
    }

    /**
     * Resume a paused subscription.
     *
     * @return $this
     */
    public function unpause()
    {
        $this->api->subscriptions->unpause($this->payfast_token);

        $this->forceFill([
            'payfast_status' => self::STATUS_ACTIVE,
            'ends_at' => null,
            'paused_from' => null,
        ])->save();

        $this->payfastInfo = null;

        return $this;
    }

    /**
     * Update the underlying Payfast subscription information for the model.
     *
     * @param  array  $options
     * @return array
     */
    public function updatePayfastSubscription(array $options)
    {

        $response = $this->api->subscriptions->update($this->payfast_token, $options);

        $this->payfastInfo = null;

        return $response;
    }


    /**
     * Cancel the subscription at the end of the current billing period.
     *
     * @return $this
     */
    public function cancel()
    {
        if ($this->onGracePeriod()) {
            return $this;
        }

        if ($this->onPausedGracePeriod() || $this->paused()) {
            $endsAt = $this->paused_from->isFuture()
                ? $this->paused_from
                : Carbon::now();
        } else {
            $endsAt = $this->onTrial()
                ? $this->trial_ends_at
                : $this->nextPayment()->date();
        }

        return $this->cancelAt($endsAt);
    }

    /**
     * Cancel the subscription immediately.
     *
     * @return $this
     */
    public function cancelNow()
    {
        return $this->cancelAt(Carbon::now());
    }

    /**
     * Cancel the subscription at a specific moment in time. Currently only supports Monthly frequency changes
     *
     * @param  \DateTimeInterface  $endsAt
     * @return $this
     */
    public function cancelAt(DateTimeInterface $endsAt)
    {

        $cycles = $this->$this->payfastInfo()['cycles'];
        $completedCycles = $this->$this->payfastInfo()['cycles_complete'];
        $remainingCycles = $cycles - $completedCycles;
        $endDate = Carbon::parse($endsAt);
        $now = Carbon::createFromFormat('Y-m-d', Carbon::now(), 'UTC')->addMonths($remainingCycles);

        if ($endDate->isFuture()) {
            $cycleDifference = $now->diffInMonths($endDate);
            $updatedCycles = $cycleDifference - $cycles;
            $this->api->subscriptions->update($this->payfast_token, ['cycles' => $updatedCycles]);
            $this->forceFill([
                'cancelled_at' => $now,
                'payfast_status' => self::STATUS_PENDING_DELETE,
                'ends_at' => Carbon::parse($this->payfastInfo()['run_date'])->addMonths($cycleDifference - 1),
            ])->save();

            $this->payfastInfo = null;

            return $this;
        }
        if ($endDate->equalTo($now)) {
            $this->api->subscriptions->cancel($this->payfast_token);
            $this->forceFill([
                'cancelled_at' => $now,
                'payfast_status' => self::STATUS_DELETED,
                'ends_at' => Carbon::parse($this->payfastInfo()['run_date']),
            ])->save();

            $this->payfastInfo = null;

            return $this;
        }
    }

    /**
     * Get the last payment for the subscription.
     *
     * @return \EllisSystems\Payfast\Payment
     */
    public function lastPayment()
    {
        $payment = $this->payfastInfo()['last_cycle'];

        return new Payment($payment['amount'], $payment['currency'], $payment['date']);
    }

    /**
     * Get the next payment for the subscription.
     *
     * @return \EllisSystems\Payfast\Payment|null
     */
    public function nextPayment()
    {
        if (!isset($this->payfastInfo()['run_date'])) {
            return;
        }

        $payment = $this->payfastInfo()['run_date'];

        return new Payment($payment['amount'], $payment['currency'], $payment['date']);
    }

    /**
     * Get the payment method type from the subscription.
     *
     * @return string
     */
    public function paymentMethod()
    {
        return (string) ($this->payfastInfo()['payment_method'] ?? '');
    }

    /**
     * Return the URL for updating card details associated with subscription
     *
     * @return string
     */
    public function  updateUrl()
    {
        return (string) ('https://' . (config('cashier.sandbox') ? 'www' : 'sandbox') . '.payfast.co.za/eng/' . ($this->payfast_token) . '?return=' . config('cashier.return_url'));
    }



    /**
     * Get raw information about the subscription from Payfast.
     *
     * @return array
     */
    public function payfastInfo()
    {

        if ($this->payfastInfo) {
            return $this->payfastInfo;
        }

        return $this->payfastInfo = $this->api->subscriptions->fetch($this->payfast_token);
    }

    /**
     * Perform a guard check to prevent change for a specific action.
     *
     * @param  string  $action
     * @return void
     *
     * @throws \LogicException
     */
    public function guardAgainstUpdates($action): void
    {
        if ($this->onTrial()) {
            throw new LogicException("Cannot $action while on trial.");
        }

        if ($this->paused() || $this->onPausedGracePeriod()) {
            throw new LogicException("Cannot $action for paused subscriptions.");
        }

        if ($this->cancelled() || $this->onGracePeriod()) {
            throw new LogicException("Cannot $action for cancelled subscriptions.");
        }

        if ($this->pastDue()) {
            throw new LogicException("Cannot $action for past due subscriptions.");
        }
    }
}

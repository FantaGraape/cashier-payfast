<?php

namespace EllisSystems\Payfast;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use EllisSystems\Payfast\Concerns\Prorates;
use LogicException;
use PayFast\PayFastPayment;

/**
 * @property \EllisSystems\Payfast\Billable $billable
 */
class Order extends Model
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
        'subscription_plan' => 'string',
        'checkout_total' => 'string',
        'ip_address' => 'string',
    ];

    /**
     * The cached Paddle info for the subscription.
     *
     * @var array
     */
    /* protected $paddleInfo; */

    /**
     * Get the billable model related to the order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function billable()
    {
        return $this->morphTo();
    }

    public function subscription()
    {
        return $this->belongsTo(Cashier::$subscriptionModel);
    }
    /**
     * Get all of the receipts for the Billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    /*  public function receipts()
    {
        return $this->hasMany(Cashier::$receiptModel, 'paddle_subscription_id', 'paddle_id')->orderByDesc('created_at');
    } */

    /**
     * Determine if the subscription has a specific plan.
     *
     * @param  int  $plan
     * @return bool
     */
    /* public function hasPlan($plan)
    {
        return $this->paddle_plan == $plan;
    } */

    public function payfastPaymentApi()
    {
        $api = new PayFastPayment(
            [
                'merchantId' => config('cashier.merchant_id'),
                'merchantKey' => config('cashier.merchant_key'),
                'passPhrase' => config('cashier.passphrase'),
                'testMode' => config('cashier.sandbox')
            ]
        );
        return $api;
    }


    public function generateOrder($amount, $requestIP, $billable_id, $billable_type)
    {
        $customer = Cashier::$customerModel::firstOrCreate([
            'billable_id' => $billable_id,
            'billable_type' => $billable_type,
        ])->billable;

        $order = $customer->orders()->create([
            'billable_id' => $billable_id,
            'billable_type' => $billable_type,
            'checkout_total' => $amount,
            'ip_address' => $requestIP,
        ]);

        $props = array(
            'm_payment_id' => $order->id,
            'notify_url' => config('cashier.notify_url'),
            'name_first' => $customer->last_name,
            'name_last' => $customer->first_name,
            'email_address' => $customer->email,
            'item_name' => $order->id,
            'custom_int1' => $billable_id,
            'custom_str1' => $billable_type,
        );
        $uuid = $this->payfastPaymentApi->onsite->generatePaymentIdentifier($props);
        return $uuid;
    }
}

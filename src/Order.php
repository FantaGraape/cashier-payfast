<?php

namespace EllisSystems\Payfast;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use EllisSystems\Payfast\Concerns\Prorates;
use LogicException;

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
}

<?php

namespace Laravel\Paddle;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Laravel\Paddle\Concerns\Prorates;
use LogicException;

/**
 * @property \Laravel\Paddle\Billable $billable
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
        'checkout_total' => 'string',
        'ip_address' => 'string',
    ];

    /**
     * Get the billable model related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function billable()
    {
        return $this->morphTo();
    }
}

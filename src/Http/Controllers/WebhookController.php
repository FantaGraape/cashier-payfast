<?php

namespace EllisSystems\Payfast\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use EllisSystems\Payfast\Cashier;
use EllisSystems\Payfast\Events\PaymentSucceeded;
use EllisSystems\Payfast\Events\SubscriptionCancelled;
use EllisSystems\Payfast\Events\SubscriptionCreated;
use EllisSystems\Payfast\Events\SubscriptionPaymentFailed;
use EllisSystems\Payfast\Events\SubscriptionPaymentSucceeded;
use EllisSystems\Payfast\Events\SubscriptionUpdated;
use EllisSystems\Payfast\Events\WebhookHandled;
use EllisSystems\Payfast\Events\WebhookReceived;
use EllisSystems\Payfast\Exceptions\InvalidPassthroughPayload;
use EllisSystems\Payfast\Http\Middleware\VerifyWebhookNotification;
use EllisSystems\Payfast\Subscription;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Create a new WebhookController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(VerifyWebhookNotification::class);
    }

    /**
     * Handle a Paddle webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    /**
     * Handle a Payfast webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request)
    {
        $payload = $request->all();
        if (isset($payload['token'])) {
            switch ($payload['payment_status']) {
                case 'COMPLETE':
                    if ($this->findSubscription($payload['token'])) {
                        $this->handleSubscriptionPaymentSucceeded($payload);
                        WebhookHandled::dispatch($payload);
                        return new Response('Webhook SubscriptionPayment handled');
                    } else {
                        $this->handleSubscriptionCreated($payload);
                        WebhookHandled::dispatch($payload);
                        return new Response('Webhook SubscriptionCreated handled');
                    }
                    break;
                case 'CANCELLED':
                    $this->handleSubscriptionCancelled($payload);
                    WebhookHandled::dispatch($payload);
                    return new Response('Webhook SubscriptionCancelled handled');
                    break;
                default:
                    return new Response('Webhook Skipped');
            }
        }
        if (!isset($payload['token'])) {
            $this->handlePaymentSucceeded($payload);
            WebhookHandled::dispatch($payload);
            return new Response('Webhook OneTimePayment handled');
        }
        return new Response();
    }

    /**
     * Handle one-time payment succeeded.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handlePaymentSucceeded(array $payload)
    {
        if ($this->receiptExists($payload['m_payment_id'])) {
            return;
        }

        $customer = $this->findOrCreateCustomer($payload['custom_str1']);

        $receipt = $customer->receipts()->create([
            'order_id' => $payload['m_payment_id'],
            'payfastPayment_id' => $payload['pf_payment_id'],
            'item_name' => $payload['item_name'],
            'item_description' => $payload['item_description'],
            'amount_gross' => $payload['amount_gross'],
            'amount_fee' => $payload['amount_fee'],
            'amount_net' => $payload['amount_net'],
            'currency' => config('cashier.currency'),
            'quantity' => (int) 1 /* $payload['quantity'] */,
            'paid_at' => Carbon::now(),
        ]);

        PaymentSucceeded::dispatch($customer, $receipt, $payload);
    }

    /**
     * Handle subscription payment succeeded.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionPaymentSucceeded(array $payload)
    {
        if ($this->receiptExists($payload['order_id'])) {
            return;
        }

        if ($subscription = $this->findSubscription($payload['token'])) {
            $billable = $subscription->billable;
        } else {
            $billable = $this->findOrCreateCustomer($payload['custom_str1']);
        }

        $receipt = $billable->receipts()->create([
            'payfast_token' => $payload['token'],
            'order_id' => $payload['m_payment_id'],
            'payfastPayment_id' => $payload['pf_payment_id'],
            'item_name' => $payload['item_name'],
            'item_description' => $payload['item_description'],
            'amount_gross' => $payload['amount_gross'],
            'amount_net' => $payload['amount_net'],
            'amount_fee' => $payload['amount_fee'],
            //Payfast only supports ZAR for now so we will use the config value - FUTURE USE
            'currency' => config('cashier.currency'),
            //Payfast doesnt natively support quantity - FUTURE USE
            'quantity' => (int) 1,
            'paid_at' => Carbon::now(),
        ]);

        SubscriptionPaymentSucceeded::dispatch($billable, $receipt, $payload);
    }

    /**
     * Handle subscription payment failed.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionPaymentFailed(array $payload)
    {
        if ($subscription = $this->findSubscription($payload['token'])) {
            SubscriptionPaymentFailed::dispatch($subscription->billable, $payload);
        }
    }

    /**
     * Handle subscription created.
     *
     * @param  array  $payload
     * @return void
     *
     * @throws \EllisSystems\Payfast\Exceptions\InvalidPassthroughPayload
     */
    protected function handleSubscriptionCreated(array $payload)
    {
        $passthrough = json_decode($payload['custom_str1'], true);

        if (!is_array($passthrough) || !isset($passthrough['subscription_name'])) {
            throw new InvalidPassthroughPayload;
        }

        $customer = $this->findOrCreateCustomer($payload['custom_str1']);

        $trialEndsAt = $payload['status'] === Subscription::STATUS_TRIALING
            ? Carbon::createFromFormat('Y-m-d', $payload['next_bill_date'], 'UTC')->startOfDay()
            : null;

        $subscription = $customer->subscriptions()->create([
            'name' => $passthrough['subscription_name'],
            'order_id' => $payload['m_payment_id'],
            'payfast_token' => $payload['token'],
            'last_cycle' => Carbon::now(),
            'next_cycle' => $payload['billing_date'],
            'payfast_status' => ($payload['payment_status'] == 'COMPLETE') ? 'active' : 'deleted',
            'quantity' => (int) 1,
            'trial_ends_at' => $trialEndsAt,
        ]);

        SubscriptionCreated::dispatch($customer, $subscription, $payload);
    }

    /**
     * Handle subscription updated.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionUpdated(array $payload)
    {
        if (!$subscription = $this->findSubscription($payload['token'])) {
            return;
        }

        // Plan...
        /* if (isset($payload['subscription_plan_id'])) {
            $subscription->paddle_plan = $payload['subscription_plan_id'];
        } */

        // Status...
       /*  if (isset($payload['status'])) {
            $subscription->paddle_status = $payload['status'];
        } */

        // Quantity...
        /* if (isset($payload['new_quantity'])) {
            $subscription->quantity = $payload['new_quantity'];
        } */

        // Paused...
        if (isset($payload['paused_from'])) {
            $subscription->paused_from = Carbon::createFromFormat('Y-m-d H:i:s', $payload['paused_from'], 'UTC');
        } else {
            $subscription->paused_from = null;
        }

        $subscription->save();

        SubscriptionUpdated::dispatch($subscription, $payload);
    }

    /**
     * Handle subscription cancelled.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleSubscriptionCancelled(array $payload)
    {
        if (!$subscription = $this->findSubscription($payload['token'])) {
            return;
        }

        // Cancellation date... //TODO
        if (is_null($subscription->ends_at)) {
            $subscription->ends_at = $subscription->onTrial()
                ? $subscription->trial_ends_at
                : Carbon::createFromFormat('Y-m-d', $payload['cancellation_effective_date'], 'UTC')->startOfDay();
        }

        // Status...
        if (isset($payload['status'])) {
            $subscription->payfast_status = ($payload['payment_status'] == 'CANCELLED') ? 'deleted' : 'active' ;
        }

        $subscription->paused_from = null;

        $subscription->save();

        SubscriptionCancelled::dispatch($subscription, $payload);
    }

    /**
     * Find or create a customer based on the passthrough values and return the billable model.
     *
     * @param  string  $passthrough
     * @return \EllisSystems\Payfast\Billable
     *
     * @throws \EllisSystems\Payfast\Exceptions\InvalidPassthroughPayload
     */
    protected function findOrCreateCustomer(string $passthrough)
    {
        $passthrough = json_decode($passthrough, true);

        if (!is_array($passthrough) || !isset($passthrough['billable_id'], $passthrough['billable_type'])) {
            throw new InvalidPassthroughPayload;
        }

        return Cashier::$customerModel::firstOrCreate([
            'billable_id' => $passthrough['billable_id'],
            'billable_type' => $passthrough['billable_type'],
        ])->billable;
    }

   /**
     * Find the first subscription matching a Payfast subscription token.
     *
     * @param  string  $subscriptionId
     * @return \EllisSystems\Payfast\Subscription|null
     */
    protected function findSubscription(string $payfastToken)
    {
        return Cashier::$subscriptionModel::firstWhere('payfast_token', $payfastToken);
    }

    /**
     * Determine if a receipt with a given Order ID already exists.
     *
     * @param  string  $orderId
     * @return bool
     */
    protected function receiptExists(string $orderId)
    {
        return Cashier::$receiptModel::where('order_id', $orderId)->count() > 0;
    }
}

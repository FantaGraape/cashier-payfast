<?php

namespace Tests\Feature;

use Carbon\Carbon;
use EllisSystems\Payfast\Cashier;
use EllisSystems\Payfast\Order;
use LogicException;

class OrdersTest extends FeatureTestCase
{
    public function test_it_can_an_order_and_return_payfast_payment_uuid()
    {
        $order = new Order([
            'amount' => '12.45',
            'tax' => '4.36',
            'currency' => 'EUR',
        ]);
        //WIP
        $this->assertSame('€12.45', $order->amount());
        $this->assertSame('12.45', $order->amount);
        $this->assertSame('€4.36', $order->tax());
        $this->assertInstanceOf(Currency::class, $order->currency());
        $this->assertSame('ZAR', $order->currency()->getCode());
    }
}

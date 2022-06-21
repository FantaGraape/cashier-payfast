<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            /* https://developers.payfast.co.za/docs#subscriptions */
            $table->id();
            $table->unsignedBigInteger('billable_id');
            $table->string('billable_type');
            $table->string('name');
            /* $table->integer('paddle_id')->unique(); Replaced by ... */
            $table->integer('payfast_token')->unique();
            /* $table->string('paddle_status'); Replaced by ... */
            $table->string('payfast_status');
            /* Billing Cycle (3)Monthly, (4)Quarterly, (5)BiAnnually, (6)Annually */
            $table->integer('frequency');
            $table->unsignedBigInteger('order_id');
            $table->integer('subscription_plan');
            $table->string('payment_method')->nullable();
            $table->integer('quantity');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('next_cycle')->nullable();
            $table->timestamp('paused_from')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['billable_id', 'billable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}

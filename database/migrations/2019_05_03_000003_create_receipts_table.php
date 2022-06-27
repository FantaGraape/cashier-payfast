<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billable_id');
            $table->string('billable_type');
            /* $table->unsignedBigInteger('paddle_subscription_id')->nullable()->index(); Replaced by ... */
            $table->string('payfast_token')->nullable()->index();
            $table->string('item_name');
            $table->string('item_description')->nullable();
            $table->string('amount_gross');
            $table->string('amount_fee');
            $table->string('amount_net');
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('payfastPayment_id');
            $table->string('currency', 3);
            $table->integer('quantity');
            $table->timestamp('paid_at');
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
        Schema::dropIfExists('receipts');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('invoiceId')->nullable();
            $table->string('email');
            $table->integer('user_id');
            $table->double('amount');
            $table->string('currency');
            $table->string('description');
            $table->string('status');
            $table->dateTime('createdAt')->nullable();
            $table->string('type');
            $table->integer('retries')->default(0);
            $table->boolean('checked')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contests', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->string('matchupType');
            // this will probably never change and always will be just H2H
            $table->string('type');
            $table->integer('size');
            $table->integer('entryFee');
            $table->string('tier')->nullable();
            $table->string('position');
            $table->boolean('filled');
            $table->string('status');
            $table->string('slate_id');
            $table->dateTime('start');
            $table->integer('user_id');
            $table->string('group_id')->nullable();
            $table->boolean('admin_contest')->default(false);
            $table->boolean('private')->default(false);
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
        Schema::dropIfExists('contests');
    }
}

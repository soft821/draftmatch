<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimeFramesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_frames', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api_week');
            $table->string('api_season');
            $table->string('week');
            $table->string('season');
            $table->dateTime('start_date');
            $table->dateTime('first_game');
            $table->dateTime('last_game');
            $table->string('season_type');
            $table->string('status')->default('current')->unique();
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
        Schema::dropIfExists('time_frames');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slates', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->string('name');
            $table->string('firstDay');
            $table->string('lastDay');
            $table->boolean('active');
            $table->string('status')->default('PENDING');
            $table->dateTime('firstGame')->nullable();
            $table->dateTime('lastGame')->nullable();
            $table->timestamps();
        });

        Schema::create('fantasy_player_slate', function (Blueprint $table) {
            $table->string('fantasy_player_id')->index();
            $table->foreign('fantasy_player_id')->references('id')->on('fantasy_players');

            $table->string('slate_id')->index();
            $table->foreign('slate_id')->references('id')->on('slates');

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
        Schema::dropIfExists('slates');
        Schema::dropIfExists('fantasy_player_slate');
    }
}

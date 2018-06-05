<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->string('year');
            $table->string('seasonType');
            $table->string('week');
            $table->string('day');
            $table->dateTime('date');
            $table->string('time');
            $table->integer('homeScore');
            $table->integer('awayScore');
            $table->string('homeTeam');
            $table->string('awayTeam');
            $table->string('status')->default('PENDING');
            $table->string('quarter')->nullable();
            $table->boolean('overtime');
            $table->string('time_remaining')->nullable();
            $table->timestamps();
        });

        Schema::create('game_slate', function (Blueprint $table) {
            $table->string('game_id')->index();
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');

            $table->string('slate_id')->index();
            $table->foreign('slate_id')->references('id')->on('slates')->onDelete('cascade');

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
        Schema::dropIfExists('games');
        Schema::dropIfExists('game_slate');
    }
}

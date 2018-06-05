<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('contest_id');
            $table->string('slate_id');
            $table->integer('user_id')->nullable();
            $table->string('username')->nullable();
            $table->string('fantasy_player_id');
            $table->string('game_id');
            $table->boolean('owner');
            $table->double('points')->default(0);
            $table->double('winning')->default(0);
            $table->boolean('winner')->default(false);
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
        Schema::dropIfExists('entries');
    }
}

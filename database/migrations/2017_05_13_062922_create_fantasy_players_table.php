<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFantasyPlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fantasy_players', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->string('name');
            $table->string('team');
            $table->string('game_id')->nullable();
            $table->string('position');
            $table->string('status')->nullable();
            $table->string('status_code')->nullable();
            $table->string('status_color')->nullable();
            $table->string('tier')->nullable();
            $table->integer('salary');
            $table->boolean('activated')->default(false);
            $table->boolean('played')->default(false);

            $table->string('tds')->default('0');
            $table->string('paYd')->default('0');
            $table->string('paTd')->default('0');
            $table->string('int')->default('0');
            $table->string('ruYd')->default('0');
            $table->string('ruTd')->default('0');
            $table->string('fum')->default('0');
            $table->string('rec')->default('0');
            $table->string('reYd')->default('0');
            $table->string('reTd')->default('0');
            $table->string('krTd')->default('0');// kick return touchdowns KickReturnTouchdowns
            $table->string('prTd')->default('0');// punt return touchdowns PuntReturnTouchdowns
            $table->string('frTd')->default('0');// fumble return touchdowns FumbleReturnTouchdowns
            $table->string('convRec')->default('0');// two point conversion receptions TwoPointConversionReceptions
            $table->string('convPass')->default('0');// two point conversion passes TwoPointConversionPasses
            $table->string('convRuns')->default('0'); // two point conversion runs TwoPointConversionRuns
            $table->string('fg0_39')->default('0'); // field goals 0-19   FieldGoalsMade0to19
            $table->string('fg40_49')->default('0'); // field goals 40-49 FieldGoalsMade40to49
            $table->string('fg50')->default('0'); // field goals 50+      FieldGoalsMade50Plus
            $table->string('xp')->default('0'); // extra points made      ExtraPointsMade
            $table->string('sacks')->default('0'); // Sacks
            $table->string('defInt')->default('0'); // Interceptions
            $table->string('fumRec')->default('0'); // FumblesRecovered
            $table->string('safeties')->default('0');  // Safeties
            $table->string('defTds')->default('0'); // DefensiveTouchdowns
            $table->string('ptsA')->default('0');
            $table->string('convRet')->default('0'); //TwoPointConversionReturns
            $table->string('fps')->default('0');
            $table->string('fps_live')->default('0');
            $table->boolean('active')->default(true);
            $table->boolean('updated')->default(false);

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
        Schema::dropIfExists('fantasy_players');
    }
}

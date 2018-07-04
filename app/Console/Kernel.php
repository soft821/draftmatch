<?php

namespace App\Console;

use App\Console\Commands\UpdateInfo;
use App\Helpers\CoinbaseHelper;
use App\Helpers\DatesHelper;
use App\TimeFrame;
use App\Game;
use App\Slate;
use App\FantasyPlayer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use MongoDB\Driver\Command;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\FoxSportsScraper::class,
        Commands\FantasyProsScraper::class,
        Commands\UpdateInfo::class,
        Commands\FantasyDataService::class,
        Commands\TimeFrameUpdate::class,
        Commands\UpdateGames::class,
        Commands\UpdatePlayerInfo::class,
        Commands\UpdatePlayerStats::class,
        Commands\UpdateLivePlayerStats::class,
        Commands\BitPaySetKey::class,
        // Commands\PullDataFrom2017::class
        Commands\UpdateRankingInfo::class

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //https://laravel.com/docs/5.4/scheduling
        \Log::info("SCHEDULING for day ".DatesHelper::getCurrentDay().' DATE : '.DatesHelper::getCurrentDate());
        //->timezone('America/New_York');
        // $schedule->command('update:info')
          //        ->everyMinute();


        $schedule->call(function() {\Log::info('Update exchange ...');CoinbaseHelper::updateExchangeRate();})->everyTenMinutes();
        $schedule->call(function() {\Log::info('Check invoices ...');UpdateInfo::checkInvoices();})->everyMinute();


        $schedule->command('update:ranking')->dailyAt('10:00')->timezone('America/New_York')->when(function(){
            \Log::info('Checking conditions for update::ranking weekly');
            if (DatesHelper::getCurrentDay() === 'Tuesday') {
                \Log::info('Condition is true for update:ranking weekly');
                return true;
            }
            \Log::info('Condition is false for update:ranking weekly');
            return false;
        });

        $schedule->command('update:info')->hourlyAt(11)->timezone('America/New_York')->when(function(){
            \Log::info('Checking conditions for update::info hourly');
            if (DatesHelper::getCurrentDay() === 'Tuesday' || DatesHelper::getCurrentDay() === 'Wednesday' ||
                DatesHelper::getCurrentDay() === 'Friday' ) {
                \Log::info('Condition is true for update:info hourly');
                return true;
            }
            \Log::info('Condition is false for update:info hourly');
            return false;
        });

        $schedule->command('update:info')->everyFiveMinutes()->timezone('America/New_York')->when(function(){
            \Log::info('Checking conditions for update::info everyFiveMinutes');
            if (DatesHelper::getCurrentDay() === 'Tuesday' || DatesHelper::getCurrentDay() === 'Wednesday' ||
                DatesHelper::getCurrentDay() === 'Friday' ) {
                return false;
                Log::info('Condition is false for update:info everyFiveMinutes');
            }
            \Log::info('Condition is true for update:info everyFiveMinutes');
            return true;
        });

        $schedule->command('timeframe:update')->hourly()->timezone('America/New_York')->when(function(){
            \Log::info("Checking condition for timeframe:update hourly ...");
            if (DatesHelper::getCurrentDay() === 'Thursday' || DatesHelper::getCurrentDay() === 'Friday' ||
                DatesHelper::getCurrentDay() === 'Saturday' || DatesHelper::getCurrentDay() === 'Sunday'){
                \Log::info("Condition for timeframe:update hourly for day condition is false");
                return false;
            }

            $current = TimeFrame::getCurrentTimeFrame();

            if (!$current){
                \Log::info('Condition for timeframe:update hourly for non-existing current timeframe is true');
                return true;
            }

            \Log::info('Retrieving games which are not in HISTORY state for timeframe:update hourly');
            //
            $games = Game::where('status', '!=', 'HISTORY')->get();
            $fps = FantasyPlayer::where('updated', '=', false)->get();

            \Log::info('Retrieved games which are not in HISTORY state for timeframe:update hourly');
            if ($games->count() === 0 && $fps->count() === 0){
                \Log::info('Condition for timeframe:update hourly for !HISTORY games is true');
                return true;
            }

            \Log::info('Condition for timeframe:update hourly false');
            return false;
        });

        $schedule->command('games:update')->withoutOverlapping()->twiceDaily(6, 18)->timezone('America/New_York')->when(function() {
            \Log::info('Condition for games:update twiceDaily is met');
            return true;
        });

        $schedule->command('games:update')->withoutOverlapping()->everyFiveMinutes()->timezone('America/New_York')->when(function(){
            \Log::info('Checking conditions for games:update everyFiveMinutes');
            //if (DatesHelper::getCurrentDay() === 'Tuesday' || DatesHelper::getCurrentDay() === 'Monday' ||
              if(  DatesHelper::getCurrentDay() === 'Wednesday') {
                \Log::info('Condition for games:update everyFiveMinuets for day condition is false '.DatesHelper::getCurrentDay());
                return false;
            }

            \Log::info('Retrieving games in progress for games:update command everyFiveMinutes');
            $gamesInProgress = Game::where('status', '=', 'LIVE')->get();
            \Log::info('Retrieved games in progress for games:update command everyFiveMinutes'.$gamesInProgress);
            if ($gamesInProgress->count() > 0){
                \Log::info('Condition for games:update everyFiveMinutes for games in progress is true ');
                return true;
            }
            \Log::info('Condition for games:update everyFiveMinutes is false');
        });

        $schedule->command('playerInfo:update')->withoutOverlapping()->twiceDaily(6, 18)->timezone('America/New_York')->when(function(){
            \Log::info('Checking conditions for playerInfo:update twiceDaily');
            if (DatesHelper::getCurrentDay() === 'Tuesday' || DatesHelper::getCurrentDay() === 'Monday' ||
                DatesHelper::getCurrentDay() === 'Wednesday' || DatesHelper::getCurrentDay() === 'Friday' ) {
                \Log::info('Condition for playerInfo:update twiceDaily for day is true '.DatesHelper::getCurrentDay());
                return true;
            }

            \Log::info('Condition for playerInfo:update twiceDaily is false');
            return false;
        });

        $schedule->command('playerInfo:update')->withoutOverlapping()->hourly()->timezone('America/New_York')->when(function(){
            \Log::info('Checking condition for playerInfo:update hourly ');
            if (DatesHelper::getCurrentDay() === 'Thursday' || DatesHelper::getCurrentDay() === 'Sunday' ||
                DatesHelper::getCurrentDay() === 'Saturday') {
                \Log::info('Condition for playerInfo:update hourly for day condition is true');
                return true;
            }

            \Log::info('Condition for playerInfo:update hourly is false');
            return false;
        });

        $schedule->command('playerStats:update')->twiceDaily(16, 22)->timezone('America/New_York');

       $schedule->command('playerStats:update')->hourly()->timezone('America/New_York')->when(function(){
           \Log::info('Checking condition for playerStats:update hourly');

            if (DatesHelper::getCurrentDay() === 'Tuesday') {
                \Log::info('Checking condition palyerStats:update hourly for Tuesday');
                $timeframe = TimeFrame::getPreviousTimeFrame();
                if (!$timeframe){
                    \Log::info('Condition for playerStats:update hourly for previous timeframe is false');
                    return false;
                }

                $key = $timeframe->retrieveKey();
                \Log::info('Condition playerStats:update hourly checking players with key '.$key);
                $fps = FantasyPlayer::where('id', 'like', '%'.$key.'%')->where('updated', '=', false)->get();
                \Log::info('Condition playerStats:update hourly successfully retrieved players with key '.$key);
                if ($fps && $fps->count() > 0){
                    \Log::info('Condition playerStats:update hourly there are players which are not updated, so condition is true');
                    return true;
                }

                \Log::info('Condition playerStats:update hourly condition is false');
                return false;
            }
        });

        /*$schedule->command('fantasyData:pull')->everyMinute()->timezone('America/New_York')->when(function(){
            \Log::info('Checking condition for fantasyData:pull everyThirtyMinutes');
            if (DatesHelper::getCurrentDay() !== 'Tuesday') {
                \Log::info('Condition for fantasyData:pull everyThirtyMinutes day condition is Tuesday');
                $timeframe = TimeFrame::getCurrentTimeFrame();
                if ($timeframe->count() === 0){
                    \Log::info('Condition for fantasyData:pull hourly for current timeframe is false');
                    return false;
                }

                \Log::info('Condition fantasyData:pull everyFiveMinutes retrieving games in progress');
                $gamesInProgress = Game::where('status', '=', 'LIVE')->get();
                \Log::info('Condition fantasyData:pull everyFiveMinutes received games in progress');
                if ($gamesInProgress->count() > 0){
                    \Log::info('Condition fantasyData:pull everyFiveMinutes there are games in progress');
                    return true;
                }

                return false;
            }
        });*/

        $schedule->command('liveStats:update')->everyThirtyMinutes()->timezone('America/New_York')->when(function(){
            \Log::info('Checking condition for liveStats:update everyThirtyMinutes');
            if (DatesHelper::getCurrentDay() !== 'Tuesday' && DatesHelper::getCurrentDay() !== 'Wednesday' &&
                DatesHelper::getCurrentDay() !== 'Friday') {
                \Log::info('Condition for liveStats:update everyThirtyMinutes day condition is Tuesday');
                $timeframe = TimeFrame::getCurrentTimeFrame();
                if (!$timeframe){
                    \Log::info('Condition for liveStats:update hourly for current timeframe is false');
                    return false;
                }

                \Log::info('Condition liveStats:update everyFiveMinutes retrieving games in progress');
                $gamesInProgress = Game::where('status', '=', 'LIVE')->get();
                \Log::info('Condition liveStats:update everyFiveMinutes received games in progress');
                if ($gamesInProgress && $gamesInProgress->count() > 0){
                    \Log::info('Condition liveStats:update everyFiveMinutes there are games in progress');
                    return true;
                }

                return false;
            }
        });



       // $schedule->command('playerStats:update')->timezone('America/New_York')->hourly()->when(function(){

        //$schedule->command('update:info')->everyMinute();



//->withoutOverlapping();



    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}

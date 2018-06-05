<?php

namespace App\Console\Commands;

use App\FantasyPlayer;
use Illuminate\Console\Command;

use App\TimeFrame;
use GuzzleHttp\Client as HttpClient;

class TimeFrameUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timeframe:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info('Updating timeframe ');
        $previous = TimeFrame::where('status', '=', 'previous')->first();

        if ($previous){
            $previous->status = time();
            $previous->save();
            \Log::info('Successfully removed previous timeframe');
        }

        $current = TimeFrame::where('status', '=', 'current')->first();
        if ($current){
            FantasyPlayer::where('updated', '=', false)->update(['updated' => true]);

            $current->status = 'previous';
            $current->save();
            \Log::info('Successfully set timeframe to previous');
        }

        $previous = TimeFrame::getPreviousTimeFrame();

        $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);
        if (!$previous){
            \Log::info('No previous');
            $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/Timeframes/current';
        }
        else {
            \Log::info('Found previous');
            $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/Timeframes/upcoming';
        }

        \Log::info('Retrieving timeframe from url '.$url);
        $timeframes = json_decode($client->request('GET', $url)->getBody()->getContents(), true);
        \Log::info('Successfully retrieved timeframe from url '.$url);
        //https://api.fantasydata.net/v3/nfl/stats/JSON/Timeframes/current
        $api_season = $timeframes[0]["ApiSeason"];
        $api_week = $timeframes[0]["ApiWeek"];
        $week = $timeframes[0]["Week"];
        $season_type = $timeframes[0]["SeasonType"];
        $season = $timeframes[0]["Season"];
        $start_date = $timeframes[0]['StartDate'];
        $first_game = $timeframes[0]['FirstGameStart'];
        $last_game = $timeframes[0]['LastGameEnd'];

        TimeFrame::create([
            "api_week"         => $api_week,
            "api_season"       => $api_season,
            "season"           => $season,
            "start_date"       => $start_date,
            "first_game"       => $first_game,
            "last_game"        => $last_game,
            "week"             => $week,
            "season"           => $season,
            "season_type"      => $season_type,
            "status"           => "current"
      ]);
        \Log::info('Successfully created current timeframe');

        \Artisan::call('games:update');
        \Artisan::call('playerInfo:update');

    }
}

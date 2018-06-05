<?php

namespace App\Console\Commands;

use App\TimeFrame;
use App\Slate;
use App\Game;
use Illuminate\Console\Command;
use GuzzleHttp\Client as HttpClient;
use Carbon\Carbon;

class UpdateGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:update';

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

    private function pullScores(){
        \Log::info('Pulling scores ');
        $timeFrame = TimeFrame::getCurrentTimeFrame();
        $key = $timeFrame->retrieveKey();

        $testSlate = Slate::where('id', 'like', '%'.$key.'%')->get();

        if ($testSlate->count() === 0) {
            \Log::info('Creating slates ');
            /*$slate1 = Slate::updateOrCreate(array('id' => 'Thu-Mon_' . $key),
                ["name" => "Thursday-Monday (All Games)",
                    "firstDay" => "Thu", "lastDay" => "Mon",
                    "active" => true]);
            $slate2 = Slate::updateOrCreate(array('id' => 'Thu-Sun_' . $key),
                ["name" => "Thursday-Sunday",
                    "firstDay" => "Thu", "lastDay" => "Sun",
                    "active" => true]);*/
            $slate3 = Slate::updateOrCreate(array('id' => 'Sun_' . $key), ["name" => "Sunday Only",
                "firstDay" => "Sun", "lastDay" => "Sun",
                "active" => true]);
            /*$slate4 = Slate::updateOrCreate(array('id' => 'Sun-Mon_' . $key), ["name" => "Sunday-Monday",
                "firstDay" => "Sun", "lastDay" => "Mon",
                "active" => true]);*/
            //$slate5 = Slate::updateOrCreate(array('id' => 'Mon_' . $key), ["name" => "Monday Only",
            //    "firstDay" => "Mon", "lastDay" => "Mon",
            //    "active" => true]);
            \Log::info('Successfully created slates');
        }

        $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);

        $url = 'https://api.fantasydata.net/v3/nfl/scores/JSON/ScoresByWeek/'.$timeFrame->api_season.'/'.$timeFrame->api_week;
        \Log::info('Pulling scores from url '.$url);
        $games = json_decode($client->request('GET', $url)->getBody()->getContents(), true);

        $gameChanged = 0;
        // @todo rename slate if there are no games, example if there is only Thursday games
        foreach ($games as $game){
            $season_type = $game['SeasonType'];
            $season = $game['Season'];
            $week = $game['Week'];
            $home_team = $game['HomeTeam'];
            $away_team = $game['AwayTeam'];
            $home_score = $game['HomeScore'];
            $away_score = $game['AwayScore'];
            $date = $game['Date'];
            $has_started = $game['HasStarted'];
            $is_over = $game['IsOver'];
            $quarter = $game['Quarter'];
            $overtime = $game['IsOvertime'];
            $time_remaining = $game['TimeRemaining'];

            $status = "PENDING";
            if ($is_over === true){
                $status = "HISTORY";
            }
            elseif($has_started === true){
                $status = "LIVE";
            }

            $UTC      = new \DateTimeZone("US/Eastern");
            $php_date     = new Carbon($date, $UTC);
            $php_time = date('h:i a', strtotime($date));
            $day = date('D', strtotime($php_date));

            $gameId = $away_team . '_' . $home_team . '_' . $key;
            $game = Game::find($gameId);
            if ($game){
                if ($game->status !== $status){
                    $gameChanged++;
                }
            }
            $game = Game::updateOrCreate(array('id' => $gameId), [
                "year"           => $season,
                "seasonType"     => $season_type,
                "week"           => $week,
                "date"           => $php_date,
                "day"            => $day,
                "time"           => $php_time,
                "homeScore"      => $home_score?$home_score:0,
                "awayScore"      => $away_score?$away_score:0,
                "homeTeam"       => $home_team,
                "awayTeam"       => $away_team,
                "quarter"        => $quarter?$quarter:"P",
                "status"         => $status,
                "overtime"       => $overtime,
                "time_remaining" => $time_remaining]);

            if ($day !== 'Sun')continue;
            else{
                \Log::info('Adding games to slates '.$day);
                $game->slates()->syncWithoutDetaching([
                    'Sun_' . $key]);
            }

            /*if ($testSlate->count() === 0) {
                \Log::info('Adding games to slates '.$day);
                if ($day === 'Thu') {
                    $game->slates()->syncWithoutDetaching(['Thu-Mon_' . $key,
                        'Thu-Sun_' . $key]);
                } else if ($day === 'Sun') {
                    $game->slates()->syncWithoutDetaching(['Thu-Mon_' . $key,
                        'Thu-Sun_' . $key,
                        'Sun_' . $key,
                        'Sun-Mon_' . $key]);
                } else if ($day === 'Mon') {
                    $game->slates()->syncWithoutDetaching(['Thu-Mon_' . $key,
                        'Sun-Mon_' . $key]);
                } else {
                    $game->slates()->syncWithoutDetaching(['Thu-Mon_' . $key]);
                }
            }*/
        }

        if ($testSlate->count() === 0) {
           /* Slate::updateOrCreate(array('id' => 'Thu-Mon_' . $key), ["firstGame" => $slate1->firstGameDate(),
                "lastGame" => $slate1->lastGameDate()]);
            Slate::updateOrCreate(array('id' => 'Thu-Sun_' . $key), ["firstGame" => $slate2->firstGameDate(),
                "lastGame" => $slate2->lastGameDate()]);*/
            Slate::updateOrCreate(array('id' => 'Sun_' . $key), ["firstGame" => $slate3->firstGameDate(),
                "lastGame" => $slate3->lastGameDate()]);
            /*Slate::updateOrCreate(array('id' => 'Sun-Mon_' . $key), ["firstGame" => $slate4->firstGameDate(),
                "lastGame" => $slate4->lastGameDate()]);*/
           // Slate::updateOrCreate(array('id' => 'Mon_' . $key), ["firstGame" => $slate5->firstGameDate(),
           //     "lastGame" => $slate5->lastGameDate()]);

           /* if ($slate1->games()->count() === 0){
                $slate1->status = 'ERROR';
                $slate1->active = false;
                $slate1->save();
            }
            if ($slate2->games()->count() === 0){
                $slate2->status = 'ERROR';
                $slate2->active = false;
                $slate2->save();
            }*/
            if ($slate3->games()->count() === 0){
                $slate3->status = 'ERROR';
                $slate3->active = false;
                $slate3->save();
            }
            /*if ($slate4->games()->count() === 0){
                $slate4->status = 'ERROR';
                $slate4->active = false;
                $slate4->save();
            }*/
           /* if ($slate5->games()->count() === 0){
                $slate5->status = 'ERROR';
                $slate5->active = false;
                $slate5->save();
            }*/
        }


        if ($gameChanged > 0){
            \Log::info('Games '.$gameChanged.' changed status');
            \Artisan::call('fantasyData:pull');
        }

        \Log::info('Successfully pulled all scores ');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->pullScores();
    }
}

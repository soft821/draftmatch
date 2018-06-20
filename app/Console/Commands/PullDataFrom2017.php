<?php

namespace App\Console\Commands;

use App\Game;
use App\Slate;
use App\FantasyPlayer;
use Illuminate\Console\Command;
use GuzzleHttp\Client as HttpClient;
use Storage;



class PullDataFrom2017 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fantasypull:2018';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull data from 2018 season';

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
        for($i = 2; $i < 15; $i++){
            $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);
            $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/TeamGameStats/2018REG/' . $i;
            \Log::info('Pulling data for season from url '.$url);
            $team_game_stats = json_decode($client->request('GET', $url)->getBody()->getContents(), true);

            foreach($team_game_stats as $game){
                $game_id = $game["Opponent"] . '_' . $game["Team"] . '_' . $game["GameKey"];
                $game = Game::updateOrCreate([
                    'id' => $game_id,
                    'year' => $game["Season"],
                    'seasonType'=> $game["SeasonType"],
                    'week'=> $game["Week"],
                    'day' => $game["DayOfWeek"],
                    'date' => $game["DateTime"],
                    'time'=> $game["DateTime"],
                    'homeScore' => $game["Score"],
                    'awayScore' => $game["OpponentScore"],
                    'homeTeam' => $game["Team"],
                    'awayTeam' => $game["Opponent"],
                    'status' => "FINISHED",
                    'overtime' => ($game["ScoreOvertime"] > 0 || $game["OpponentScoreOvertime"] > 0) ? true : false
                ]);


                $slate1 = Slate::updateOrCreate(array('id' => 'Thu-Mon_2017_'.$i.'_REG'),
                    ["name" => "Thursday-Monday (All Games)",
                        "firstDay" => "Thu", "lastDay" => "Mon",
                        "active"   => true, "status" => "HISTORY"]);
                $slate2 = Slate::updateOrCreate(array('id' => 'Thu-Sun_2017_'.$i.'_REG'),
                    ["name" => "Thursday-Sunday",
                        "firstDay" => "Thu", "lastDay" => "Sun",
                        "active"   => true, "status" => "HISTORY"]);
                $slate3 = Slate::updateOrCreate(array('id' => 'Sun_2017_'.$i.'_REG'),
                    ["name" => "Sunday Only",
                        "firstDay" => "Sun", "lastDay" => "Sun",
                        "active"   => true, "status" => "HISTORY"]);
                $slate4 = Slate::updateOrCreate(array('id' => 'Sun-Mon_2017_'.$i.'_REG'),
                    ["name" => "Sunday-Monday",
                        "firstDay" => "Sun", "lastDay" => "Mon",
                        "active"   => true, "status" => "HISTORY"]);
                $slate5 = Slate::updateOrCreate(array('id' => 'Mon_2017_'.$i.'_REG'),
                    ["name" => "Monday Only",
                        "firstDay" => "Mon", "lastDay" => "Mon",
                        "active"   => true, "status" => "HISTORY"]);

                $slate1->games()->attach($game_id);
                if($game["DayOfWeek"] === "Sun"){
                    $slate2->games()->attach($game_id);
                    $slate3->games()->attach($game_id);
                    $slate4->games()->attach($game_id);
                }
                else if($game["DayOfWeek"] === "Mon"){
                    $slate4->games()->attach($game_id);
                    $slate5->games()->attach($game_id);
                }
                else if($game["DayOfWeek"] === "Thu"){
                    $slate2->games()->attach($game_id);
                }

            }

        }

        $slates = Slate::where('active', true)->where('status', 'HISTORY')->get();
        foreach ($slates as $slate){
            $slate->firstGame = $slate->firstGameDate();
            $slate->lastGame = $slate->lastGameDate();
            $slate->save();
            $this->addPlayers($slate->games()->get());
        }
    }

    public function addPlayers($games){
        $fantasy_players = array(
            array(
                "name" =>"Julio Jones",
                "team" => "ATL",
                "position" => "WR",
                "status" => "Healthy",
                "status_code" => "ACT",
                "status_color" => "green",
                "tier" => "A",
                "salary" => 8600
            ),
            array(
                "name"=> "Marqise Lee",
                "team"=> "JAX",
                "position"=> "WR",
                "status"=> "Healthy",
                "status_code"=> "ACT",
                "status_color"=> "green",
                "tier"=> "D",
                "salary"=> 6000,
            ),
            array(
                "name"=> "Devontae Booker",
                "team"=> "DEN",
                "position"=> "RB",
                "status"=> "Healthy",
                "status_code"=> "ACT",
                "status_color"=> "green",
                "tier"=> "E",
                "salary"=> 5400,
            ),
            array(
                "name"=> "Cole Beasley",
                "team"=> "DAL",
                "position"=> "WR",
                "status"=> "Healthy",
                "status_code"=> "ACT",
                "status_color"=> "green",
                "tier"=> "E",
                "salary"=> 5400,
            ),
            array(
                "name"=> "Jerick McKinnon",
                "team"=> "MIN",
                "position"=> "RB",
                "status"=> "Healthy",
                "status_code"=> "ACT",
                "status_color"=> "green",
                "tier"=> "D",
                "salary"=> 6100,

            ),
            array(
                "name"=> "Latavius Murray",
                "team"=> "MIN",
                "position"=> "RB",
                "status"=> "Healthy",
                "status_code"=> "ACT",
                "status_color"=> "green",
                "tier"=> "C",
                "salary"=> 6500,
            ),
            array(
                "name"=> "Lance Kendricks",
                "team"=> "GB",
                "position"=> "TE",
                "status"=> "Healthy",
                "status_code"=> "ACT",
                "status_color"=> "green",
                "tier"=> "E",
                "salary"=> 4500,
            ),
            array(
                "name"=> "Le'Veon Bell",
                "team"=> "PIT",
                "position"=> "RB",
                "status"=> "Healthy",
                "status_code"=> "ACT",
                "status_color"=> "green",
                "tier"=> "A",
                "salary"=> 9400,
            ),
            array(
                "name"=> "Alex Smith",
                "team"=> "KC",
                "position"=> "QB",
                "status"=> "Healthy",
                "status_code"=> "ACT",
                "status_color"=> "green",
                "tier"=> "B",
                "salary"=> 7900,
            ),
            array(
                "name"=> "Dak Prescott",
                "team"=> "DAL",
                "position"=> "QB",
                "status"=> "Healthy",
                "status_code"=> "ACT",
                "status_color"=> "green",
                "tier"=> "A",
                "salary"=> 8500,
            ));

        foreach ($games as $game){
            foreach ($fantasy_players as $fp){
                if($fp["team"] === $game["homeTeam"] || $fp["team"] === $game["awayTeam"]){
                    FantasyPlayer::updateOrCreate(
                        array(
                            "id" => mt_rand(1000000000, 9999999999),
                            "game_id" => $game['id'],
                            "name"=> $fp['name'],
                            "team"=> $fp['team'],
                            "position"=> $fp['position'],
                            "status"=> $fp['status'],
                            "status_code"=> $fp['status_code'],
                            "status_color"=> $fp['status_color'],
                            "tier"=> $fp['tier'],
                            "salary"=> $fp['salary'],
                        )
                    );
                    unset($fp);
                }
            }
        }
    }
}

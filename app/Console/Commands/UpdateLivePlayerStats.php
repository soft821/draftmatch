<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\TimeFrame;
use App\FantasyPlayer;
use GuzzleHttp\Client as HttpClient;

class UpdateLivePlayerStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'liveStats:update';

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

    public function pullPlayerStats(){
        // https://api.fantasydata.net/v3/nfl/stats/JSON/PlayerGameStatsByWeek/2017PRE/3

        $timeFrame = TimeFrame::getCurrentTimeFrame();
        $key = $timeFrame->retrieveKey();

        \Log::info("Updating live player stats ...");
        $playerInfos = FantasyPlayer::where('id', 'like', '%'.$key.'%')->get();

        if ($playerInfos->count() === 0){
            \Log::info('IT IS 0');
            return;
        }

        \Log::info('Not 0');
        $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);
        // $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/PlayerGameStatsByWeekDelta/'.$timeFrame->api_season.'/'.$timeFrame->api_week.'/2';
        $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/PlayerGameStatsByWeekDelta/2017REG/'.$timeFrame->api_week.'/2';
        \Log::info('Pulling player stats from url '.$url);
        $players = json_decode($client->request('GET', $url)->getBody()->getContents(), true);

        $counter = 0;
        foreach($players as $player){
            $position = $player['PositionCategory'];
            $id = $player['PlayerID'];
            $game_over = $player['IsGameOver'];
            if ($position === 'DEF'){
                continue;
            }
            $tds  = $player['Touchdowns'];
            $paYd = $player['PassingYards'];
            $paTd = $player['PassingTouchdowns'];
            $int  = $player['PassingInterceptions'];
            $ruYd = $player['RushingYards'];
            $ruTd = $player['RushingTouchdowns'];
            $fum  = $player['FumblesLost'];
            $rec  = $player['Receptions'];
            $reYd = $player['ReceivingYards'];
            $reTd = $player['ReceivingTouchdowns'];
            $krTd = $player['KickReturnTouchdowns'];
            $prTd = $player['PuntReturnTouchdowns'];
            $frTd = $player['FumbleReturnTouchdowns'];
            $convRec = $player['TwoPointConversionReceptions'];
            $convPass = $player['TwoPointConversionPasses'];
            $convRuns = $player['TwoPointConversionRuns'];
            $fg0_19 = $player['FieldGoalsMade0to19'];
            $fg20_29 = $player['FieldGoalsMade20to29'];
            $fg30_39 = $player['FieldGoalsMade30to39'];
            $fg40_49 = $player['FieldGoalsMade40to49'];
            $fg50 = $player['FieldGoalsMade50Plus'];
            $xp = $player['ExtraPointsMade'];
            $fps = $player['FantasyPointsFanDuel'];

            $fg0_39 = $fg0_19 + $fg20_29 + $fg30_39;

            $playerId = $id .'_'.$key;

            $fantasyPlayer = FantasyPlayer::find($playerId);
            if ($fantasyPlayer && !$fantasyPlayer->updated) {
                $counter++;
                $fantasyPlayer->update([
                    "tds" => $tds,
                    "paYd" => $paYd,
                    "paTd" => $paTd,
                    "int" => $int,
                    "ruYd" => $ruYd,
                    "ruTd" => $ruTd,
                    "fum" => $fum,
                    "rec" => $rec,
                    "reYd" => $reYd,
                    "reTd" => $reTd,
                    "krTd" => $krTd,
                    "prTd" => $prTd,
                    "frTd" => $frTd,
                    "convRec" => $convRec,
                    "convPass" => $convPass,
                    "convRuns" => $convRuns,
                    "fg0_39" => $fg0_39,
                    "fg40_49" => $fg40_49,
                    "fg50" => $fg50,
                    "xp" => $xp,
                    "updated" => $game_over,
                    "active" => !$game_over,
                    "fps_live" => $fps]);
            }

        }

        \Log::info('Updated .'.$counter.' players ');
    }

    private function pullDefenseStats(){
        //https://api.fantasydata.net/v3/nfl/stats/JSON/FantasyDefenseByGame/2017PRE/3

        $timeFrame = TimeFrame::getCurrentTimeFrame();
        $key = $timeFrame->retrieveKey();
        $playerInfos = FantasyPlayer::where('id', 'like', '%'.$key.'%');

        if ($playerInfos->count() === 0){
            return;
        }

        $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);
        // $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/FantasyDefenseByGame/'.$timeFrame->api_season.'/'.$timeFrame->api_week;
        $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/FantasyDefenseByGame/2017REG/'.$timeFrame->api_week;
        \Log::info('Calling end point '.$url);
        $defenses = json_decode($client->request('GET', $url)->getBody()->getContents(), true);

        foreach($defenses as $defense){
            $game_over = $defense['IsGameOver'];
            $id = $defense['PlayerID'];
            $team = $defense['Team'];
            $sacks = $defense['Sacks'];
            $int = $defense['Interceptions'];
            $fum = $defense['FumblesRecovered'];
            $safties = $defense['Safeties'];
            $defTd = $defense['DefensiveTouchdowns'];
            $ptsA = $defense['PointsAllowed'];
            $conv_ret = $defense['TwoPointConversionReturns'];
            $fps = $defense['FantasyPointsFanDuel'];

            $playerId = $team .'_'.$key;

            $fantasyPlayer = FantasyPlayer::find($playerId);
            if ($fantasyPlayer && !$fantasyPlayer->updated) {
                $fantasyPlayer->update([
                    "sacks" => $sacks,
                    "defInt" => $int,
                    "fumRec" => $fum,
                    "safeties" => $safties,
                    "defTds" => $defTd,
                    "ptsA" => $ptsA,
                    "convRet" => $conv_ret,
                    "updated" => $game_over,
                    "active" => !$game_over,
                    "fps_live" => $fps]);
            }
        }

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $this->pullPlayerStats();
        $this->pullDefenseStats();
    }
}

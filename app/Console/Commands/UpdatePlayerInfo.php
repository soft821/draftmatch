<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\TimeFrame;
use App\Game;
use App\FantasyPlayer;
use GuzzleHttp\Client as HttpClient;

class UpdatePlayerInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'playerInfo:update';

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

    private function pullPlayerInfo(){
        \Log::info('Pulling player info ...');
        $timeFrame = TimeFrame::getCurrentTimeFrame();

        $newDate  =  date('Y-M-d', strtotime($timeFrame->start_date));

        $key = $timeFrame->retrieveKey();
        $gamesInRange = Game::with('slates')->where('id', 'like', '%'.$key.'%')->get();

        $gamesMap  = array();
        $slatesMap = array();
        $update = false;

        if ($gamesInRange->count() === 0){
            return;
        }
        if ($gamesInRange && $gamesInRange[0]->fantasyPlayers()->count() === 0){
            $update = true;
        }

        $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);

        $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/DailyFantasyPlayers/'.$newDate;
        \Log::info('Retrieving data from url '.$url);

        $players = json_decode($client->request('GET', $url)->getBody()->getContents(), true);
        \Log::info('Successfully retrieved data from url '.$url);

        $qb_max_salary = 0; $qb_min_salary = 100000;
        $rb_max_salary = 0; $rb_min_salary = 100000;
        $wr_max_salary = 0; $wr_min_salary = 100000;
        $te_max_salary = 0; $te_min_salary = 100000;
        $k_max_salary = 0;  $k_min_salary = 100000;
        $d_max_salary = 0;  $d_min_salary = 100000;

        foreach($gamesInRange as $game ) {
            $gamesMap[$game->homeTeam] = $game;
            $gamesMap[$game->awayTeam] = $game;
            $slatesMap[$game->id]      = $game->slates;
        }

        foreach ($players as $player){
            $fps = $player['ProjectedFantasyPoints'];
            $player_id = $player['PlayerID'];
            $name = $player['Name'];
            $team = $player['Team'];
            $position = $player['Position'];
            $salary = $player['FanDuelSalary'];
            $fantasyDraftSalary = $player['FantasyDraftSalary'];
            $status = $player['Status'];
            $status_code = $player['StatusCode'];
            $status_color = $player['StatusColor'];

            if ($position === 'DEF'){
                if ($fantasyDraftSalary && $fantasyDraftSalary > 0){
                    $salary = $fantasyDraftSalary;
                }
                $player_id = $team;
            }

            if (!$fps || $fps === '0' || $fps === 0 || $status === 'OUT'){
                continue;
            }

            if (!$salary || $salary === null || $salary === '0' || $salary === 0) {
                continue;
            }

            $playerId = $player_id.'_'.$key;

            if ($position === 'QB'){
                if ($qb_max_salary < $salary) {
                    $qb_max_salary = $salary;
                }
                if ($qb_min_salary > $salary){
                    $qb_min_salary = $salary;
                }
            }
            elseif ($position === 'RB'){
                if ($rb_max_salary < $salary) {
                    $rb_max_salary = $salary;
                }
                if ($rb_min_salary > $salary){
                    $rb_min_salary = $salary;
                }
            }
            elseif ($position === 'WR'){
                if ($wr_max_salary < $salary) {
                    $wr_max_salary = $salary;
                }
                if ($wr_min_salary > $salary){
                    $wr_min_salary = $salary;
                }
            }
            elseif ($position === 'TE'){
                if ($te_max_salary < $salary) {
                    $te_max_salary = $salary;
                }
                if ($te_min_salary > $salary){
                    $te_min_salary = $salary;
                }
            }
            elseif ($position === 'K'){
                if ($k_max_salary < $salary) {
                    $k_max_salary = $salary;
                }
                if ($k_min_salary > $salary){
                    $k_min_salary = $salary;
                }
            }
            elseif ($position === 'DEF' || $position === 'DST'){
                if ($d_max_salary < $salary) {
                    $d_max_salary = $salary;
                }
                if ($d_min_salary > $salary){
                    $d_min_salary = $salary;
                }
            }

            $game = array_key_exists($team, $gamesMap) ? $gamesMap[$team]->id : null;

            //if ($game->day != 'Sun')continue;

            if ($game !== null && $salary && $salary > 0){//} && $gamesMap[$team]->status === 'PENDING') {
                if ($position === 'DEF'){
                    $position = 'DST';
                }
                if (!$update){
                    $fantasyPlayer = FantasyPlayer::find($playerId);
                    if ($fantasyPlayer){
                        $active = true;
                        if ($status === 'OUT' && $fantasyPlayer->entries()->count() === 0){
                            $active = false;
                        }
                        $fantasyPlayer->update([
                            "name" => $name,
                            "position" => $position,
                            "salary" => $salary,
                            "team" => $team,
                            "status" => $status,
                            "status_code" => $status_code,
                            "status_color" => $status_color,
                            "fps" => $fps,
                            "active" => $active]);
                    }
                }
                else {
                    $fantasyPlayer = FantasyPlayer::updateOrCreate(array('id' => $playerId), [
                        "name" => $name,
                        "position" => $position,
                        "salary" => $salary,
                        "team" => $team,
                        "status" => $status,
                        "status_code" => $status_code,
                        "status_color" => $status_color,
                        "fps" => $fps,
                        "game_id" => $gamesMap[$team]->id]);
                    $game = $gamesMap[$team];
                    $fantasyPlayer->slates()->syncWithoutDetaching($slatesMap[$game->id]);
                }
            }
        }

        if ($update) {
            \Log::info('Updating tiers for players ...');
            $players = FantasyPlayer::where('id', 'like', '%'.$key.'%')->get();
            foreach($players as $player){
                if ($player->position === 'QB'){
                    $tier = $this->getTier($qb_max_salary, $qb_min_salary, $player->salary);
                }
                else if ($player->position === 'RB'){
                    $tier = $this->getTier($rb_max_salary, $rb_min_salary, $player->salary);
                }
                else if ($player->position === 'WR'){
                    $tier = $this->getTier($wr_max_salary, $wr_min_salary, $player->salary);
                }
                else if ($player->position === 'TE'){
                    $tier = $this->getTier($te_max_salary, $te_min_salary, $player->salary);
                }
                else if ($player->position === 'K'){
                    $tier = $this->getTier($k_max_salary, $k_min_salary, $player->salary);
                }
                else{
                    $tier = $this->getTier($d_max_salary, $d_min_salary, $player->salary);
                }

                $player->tier = $tier;
                $player->save();
            }
        }

        \Log::info('Successfully updated player info');
    }

    private function getTier($max_salary, $min_salary, $salary){
        $step = ($max_salary - $min_salary)/5.0;
        $diff = $max_salary - $salary;

        $tier = 'A';
        if ($diff <= 2 * $step && $diff > $step){
            $tier = 'B';
        }
        else if ($diff <= 3 * $step && $diff > 2 * $step){
            $tier = 'C';
        }
        else if ($diff <= 4 * $step && $diff > 3 * $step){
            $tier = 'D';
        }
        else if ($diff > 4 * $step){
            $tier = 'E';
        }

        return $tier;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info('Executing command playerInfo:update');
        $this->pullPlayerInfo();
        \Log::info('Executed command playerInfo:update successfully');
    }
}

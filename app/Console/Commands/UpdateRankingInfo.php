<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client as HttpClient;
use DB;
use App\User;
use App\Entry;
use App\Ranking_week;
use App\Ranking_month;
use Carbon\Carbon;
use Illuminate\Support\Collection;

use Illuminate\Support\Facades\Mail;
use App\Mail\RankingUpdateMail;
use Pusher\Pusher;

class UpdateRankingInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ranking';

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

    public function updateByAllTime(){
        $users = User::all();
        foreach($users as $user){
              if ($user['history_count'] > 0){
                $user['score'] = $this -> divideFloat($user['history_winning'], $user['history_count']);
              }
              else{
                $user['score'] = 0.0;
              }
              $user->update(['score'=>$user['score']]);
        }
    }

    public function updateByMonth(){
        $users = User::all();
        foreach ($users as $user) {
            $userScore = array();
            for ($i = 1; $i < 13 ; $i++){
                $startMonth = $i;
                $dateS = new Carbon('2018-'.$startMonth.'-1');
                $dateE = new Carbon('2018-'.$startMonth.'-31');
                $scoreData = DB::table('users')->where('users.id', '=', $user->id)
                    ->join('entries', 'users.id', '=', 'entries.user_id')
                    ->join('games', 'games.id', '=', 'entries.game_id')
                    ->whereBetween('games.date', [$dateS->format('Y-m-d')." 00:00:00", $dateE->format('Y-m-d')." 23:59:59"])
                    ->select('users.name','games.week', 'entries.winning')
                    ->get();
                // dd($scoreData, $userScore);
                $month_playCount = count($scoreData);
                $score = 0;
                foreach ($scoreData as $scoreValue) {
                    $score += $scoreValue->winning;
                }
                if ($month_playCount > 0){
                    $score = $this -> divideFloat($score, $month_playCount);
                }
                else{
                    $score = 0.0;
                }
                $userscore = array_push($userScore, $score);
            }

            foreach ($userScore as $key => $value) {
                $month = $key + 1;
                $userScoreObject['score_month_'.$month] = $value;
            }
            $userScoreObject['user_id'] = $user->id;
            // dd($userScoreObject);
            $ranking_months = Ranking_month::UpdateOrCreate($userScoreObject);
        }
    }

    public function updateByWeek(){
        $users = User::all();
        foreach ($users as $user) {
            $userScore = array();
            for ($i = 1; $i < 21 ; $i++){
                $scoreData = DB::table('users')->where('users.id', '=', $user->id)
                    ->join('entries', 'users.id', '=', 'entries.user_id')
                    ->join('games', 'games.id', '=', 'entries.game_id')
                    ->where('games.week', 'like', $i)
                    ->select('users.name','games.week', 'entries.winning')
                    ->get();
                $week_playCount = count($scoreData);
                $score = 0;
                foreach ($scoreData as $scoreValue) {
                    $score += $scoreValue->winning;
                }
                if ($week_playCount > 0){
                    $score = $this -> divideFloat($score, $week_playCount);
                }
                else{
                    $score = 0.0;
                }
                $userscore = array_push($userScore, $score);
            }

            foreach ($userScore as $key => $value) {
                $week = $key + 1;
                $userScoreObject['score_week_'.$week] = $value;
            }
            $userScoreObject['user_id'] = $user->id;
            $ranking_weeks = Ranking_week::UpdateOrCreate($userScoreObject);
        }
    }

    public function sendPushNotification(){
        //Remember to set your credentials below.
        $pusher = new Pusher(
            '22436cb886a06e68758b',
            '43d7fd80ce194a54f933',
            '553320',
            $options
        );
        
        $message= "Ranking updated!";
        
        //Send a message to notify channel with an event name of notify-event
        $pusher->trigger('notify', 'notify-event', $message); 
    }

    public function sendEmail(){
        Mail::to('wanga6404@gmail.com')->send(new RankingUpdateMail());
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->updateByAllTime();
        $this->updateByMonth();
        $this->updateByWeek();
        $this->sendPushNotification();
        $this->sendEmail();

    }
}

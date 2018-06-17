<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\FantasyPlayer;
use Illuminate\Console\Command;
use App\Slate;
use App\Game;
use App\TimeFrame;
use GuzzleHttp\Client as HttpClient;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\User;
use App\Invoice;
use JWTAuthException;


use App\Contest;
use App\Common\Consts\Contest\ContestStatusConsts;
use Openclerk\OAuth2\Client\Provider\Coinbase;
use Illuminate\Routing\Redirector;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;


class HomeController extends Controller
{
/**
 * Create a new controller instance.
 *
 * @return void
 */



public function __construct()
{
    //$this->middleware('auth');
}

/**
 * Show the application dashboard.
 *
 * @return \Illuminate\Http\Response
 */
    public function httpBuildQuery($params, $numeric_prefix = 0, $arg_separator = '&', $enc_type = null)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !defined('HHVM_VERSION')) {
            if ($enc_type === null) {
                $enc_type = 1;
            }
            $url = http_build_query($params, $numeric_prefix, $arg_separator, $enc_type);
        } else {
            $url = http_build_query($params, $numeric_prefix, $arg_separator);
        }

        return $url;
    }


    public function testCoinbaseOauth2(Request $request){

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$USER_ERROR_ADDING_FUNDS,
                $exception->getMessage());
        }

        $provider = new Coinbase([
        'clientId'      => 'fb93f05d512ed76a0a4d24f8de50f65f7d3ad30a4c52debce61cf50d9e091730',
        'clientSecret'  => '43362912cf29fbbe339a343315a214dc232de81b7da03e7b2fd8f3688a593ca6',
        'redirectUri'   => 'http://localhost:8000/api/coinbase/au-cb',
        'scopes'        => ['user', 'balance', 'wallet:accounts:read','wallet:transactions:read',
                            'wallet:accounts:update', 'wallet:accounts:create', 'wallet:accounts:delete',
                            'wallet:addresses:read', 'wallet:addresses:create', 'wallet:buys:read',
                            'wallet:buys:create', 'wallet:deposits:read', 'wallet:deposits:create',
                            'wallet:notifications:read', 'wallet:payment-methods:read', 'wallet:payment-methods:delete',
                            'wallet:payment-methods:limits', 'wallet:sells:read', 'wallet:sells:create', 'wallet:transactions:send',
                            'wallet:transactions:request', 'wallet:transactions:transfer', 'wallet:user:read',
                            'wallet:user:update', 'wallet:user:email', 'wallet:withdrawals:read', 'wallet:withdrawals:create', 
                            // 'wallet:transactions:send:bypass-2fa',

                            ],
       
        'authorizationHeader' => 'Bearer',
        'account'       => 'all'
        ]);

        if (!isset($_GET['code'])) {

          // If we don't have an authorization code then get one
          $authUrl = $provider->getAuthorizationUrl();

            $params = [

                    'meta[send_limit_amount]' =>  '1',
                    'meta[send_limit_currency]'=> 'USD',
                    'meta[send_limit_period]'=> 'day'
            ];

          $auth_add_Url = $authUrl.'&'.$this->httpBuildQuery($params, '', '&');
          $_SESSION['oauth2state'] = $provider->state;
          header('Location: '.$auth_add_Url);
          exit;

        // Check given state against previously stored one to mitigate CSRF attack
        // } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

        //   unset($_SESSION['oauth2state']);
        //   exit('Invalid state');

        } else {

          // Try to get an access token (using the authorization code grant)
          $token = $provider->getAccessToken('authorization_code', [
              'code' => $_GET['code']
          ]);

          // Use this to interact with an API on the users behalf
          $accessToken = $token->accessToken;

          // Use this to get a new access token if the old one expires
          $refreshToken = $token->refreshToken;

          // Number of seconds until the access token will expire, and need refreshing
          $expires = $token->expires;

          $userId = $user->id;
          $currentUser = User::find($userId);
          $currentUser->access_token = $accessToken;
          $currentUser->refresh_token = $refreshToken;
          $currentUser->expires = $expires;
          $currentUser->save();
         
          dd($currentUser);
          return redirect()->action('Api\v1\UsersController@addFunds');
          // Optional: Now you have a token you can look up a users profile data
          // try {
          //   // We got an access token, let's now get the user's details
          //   // $userDetails = $provider->getUserDetails($token);
          //   $client = new HttpClient(['headers' => ['Authorization' => 'Bearer '.$token->accessToken, 'CB-VERSION' => '2017-08-09']]);

          //   $url = 'https://api.coinbase.com/v2/user';
          //   $userDetails = json_decode($client->request('GET', $url)->getBody()->getContents(), true);

          //   // Use these details to create a new profile
          //   printf('Hello %s!', $userDetails->firstName);

          //   // You can also get Coinbase balances
          //   $balanceDetails = $provider->getBalanceDetails($token);

          //   printf('You have %f %s', $balanceDetails['amount'], $balanceDetails['currency']);

          // } catch (Exception $e) {

          //   // Failed to get user details
          //   exit('Oh dear...');
          // }

          // // Use this to interact with an API on the users behalf
          // echo $token->accessToken;

          // // Use this to get a new access token if the old one expires
          // echo $token->refreshToken;

          // // Number of seconds until the access token will expire, and need refreshing
          // echo $token->expires;
        }
    }

    public function test(){
        $adminEmail = 'daovu1118@gmail.com';
        $email = 'daovu1118@gmail.com';
        $user = 'jingZang';
        $transaction = 'pending';
        $amount = 10;
        \Mail::raw('Transaction ' . '$transaction->getStatus()' . ' for user with email ' . $email . '.', function ($message) use ($user, $adminEmail) {
            $message->subject('Failed transaction notification ' . 'soft821@outlook.com')->to($adminEmail);
        });

        \Mail::raw('Your request for adding ' . ($amount) . '$ to draftmatch is in status' . '$transaction->getStatus()', function ($message) use ($user, $transaction) {
            $message->subject('DraftMatch Deposit Failed')->to('daovu1118@gmail.com');
        });
    }

    public function index()
    {
        return redirect('http://draftmatch.com');
    }

    public function deleteAlldata(){
        Game::where('overtime', false)->delete();
        Slate::where('status', '=', 'HISTORY')->delete();
    }
    public static function getRandD($min, $max)
    {
        return (mt_rand ($min * 10, $max * 10)/10.0);
    }
    public function pullPlayerStats($games, $slate_id){
        // https://api.fantasydata.net/v3/nfl/stats/JSON/PlayerGameStatsByWeek/2017PRE/3

        $temp_week = '';
        $players;
        foreach ($games as $game) {

                $game_week = $game['week'];
                if ($game_week != '2') continue;
                $game_id = $game['id']; 
                $timeFrame = TimeFrame::getTimeFrame($game_week);
                // dd($game_week);
                $key = $timeFrame->retrieveKey();

                $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);
                $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/PlayerGameStatsByWeek/'.$timeFrame->api_season.'/'.$game_week; 

                \Log::info('Pulling player stats from url '.$url);

                if ($temp_week == $game_week) {
                    
                } else {
                    $players = json_decode($client->request('GET', $url)->getBody()->getContents(), true);
                    $temp_week = $game_week;
                }
                
                foreach($players as $player){
                    $name = $player['Name'];
                    $team = $player['Team'];
                    $real_position = $player['Position'];
                    $play_game_key = $team.$player['GameKey'];
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
                    $activated = $player['Activated'];
                    $played = $player['Played'];

                    $fg0_39 = $fg0_19 + $fg20_29 + $fg30_39;

                    $playerId = $id .'_'.$key;
                    $salary = round($this->getRandD(4000, 9000));
                    if ($salary < 5000){
                        $tier = 'E';
                    }
                    else if ($salary < 6000){
                        $tier = 'D';
                    }
                    else if ($salary < 7000){
                        $tier = 'C';
                    }
                    else if ($salary < 8000){
                        $tier = 'B';
                    }
                    else{
                        $tier = 'A';
                    }
                    $game_key = $team.$game['game_key'];
                    if ($game_key == $play_game_key){

                        
                        $fp = FantasyPlayer::UpdateOrCreate(
                            ['id' => $playerId],
                            [
                                "id" => $playerId,
                                "game_id" => $game['id'],
                                "name"=> $name,
                                "team"=> $team,
                                "position"=> $real_position,
                                "status"=> 'Healthy',
                                "status_code"=> 'ACT',
                                "status_color"=> 'green',
                                "tier"=> $tier,
                                "salary"=> $salary,
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
                                "fps_live" => $fps,
                                "activated" => $activated,
                                "played" => $played
                            ]
                        );

                       
                             $fp->slates()->sync([$slate_id], false);
                        
                    unset($player);
                    }
                   
                   
                }

           
        }
        
    }

    private function pullDefenseStats(){
        //https://api.fantasydata.net/v3/nfl/stats/JSON/FantasyDefenseByGame/2017PRE/3

        \Log::info('Pulling defense stats ...');
        
        $key = '2017_1_1';

        $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);
        $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/FantasyDefenseByGame/2017REG/1';
        $defenses = json_decode($client->request('GET', $url)->getBody()->getContents(), true);

        foreach($defenses as $defense){
            $game_over = $defense['IsGameOver'];
            $team = $defense['Team'];
            $id = $defense['PlayerID'];
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

        \Log::info('Successfully pulled defense stats');
        echo "success";

    }

    public function makeTimeFrame()
    {
         $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);
         $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/Timeframes/all';
         $timeframes = json_decode($client->request('GET', $url)->getBody()->getContents(), true);
         // $deletedRows = TimeFrame::where('id','<', '2200')->delete();
         foreach ($timeframes as $timeframe) {


                if($timeframe["Season"] > 2016){
                // dd($season);
                    $api_season = $timeframe["ApiSeason"];
                    $api_week = $timeframe["ApiWeek"];
                    // if ($timeframe["Week"] != null){
                        $week = $timeframe["Week"];
                    // }
                    $season_type = $timeframe["SeasonType"];
                    $season = $timeframe["Season"];
                    $start_date = $timeframe['StartDate'];
                    $first_game = $timeframe['FirstGameStart'];
                    $last_game = $timeframe['LastGameEnd'];
                    // dd($last_game);
                    if ($api_week!=null&&$week!=null){
                        TimeFrame::UpdateorCreate([
                        "api_week"         => $api_week,
                        "api_season"       => $api_season,
                        "season"           => $season,
                        "start_date"       => $start_date,
                        "first_game"       => $first_game,
                        "last_game"        => $last_game,
                        "week"             => $week,
                        "season"           => $season,
                        "season_type"      => $season_type,
                        // "status"           => 'current'
                    ]);
                    }
                    
                }

                
         }
          // $updaterows = TimeFrame::where('id', '<', '2200')->update(['status' => 'current']);



    }
    public function makeFulldata2017()
    {
        for($i = 2; $i < 3; $i++){
            $client = new HttpClient(['headers' => ['Ocp-Apim-Subscription-Key' => "234e0f8d08b14965a663ec86e7fd43d9"]]);
            $url = 'https://api.fantasydata.net/v3/nfl/stats/JSON/TeamGameStats/2017REG/' . $i;
            \Log::info('Pulling data for season from url '.$url);
            $team_game_stats = json_decode($client->request('GET', $url)->getBody()->getContents(), true);
            $timeFrame = TimeFrame::getTimeFrame($i);
            $key = $timeFrame->retrieveKey();
            $game_buffer = array();
            foreach($team_game_stats as $game){
                $dayOfWeek = $game['DayOfWeek'];
                $opp_game_id = $game["Team"] . '_' . $game["Opponent"] . '_' . $game["GameKey"];
                $flag = in_array($opp_game_id, $game_buffer);

                if($flag == false){
                    $team_game_id = $game["Opponent"] . '_' . $game["Team"] . '_' . $game["GameKey"];
                    array_push($game_buffer, $team_game_id);
                    $game_id = $game["Opponent"] . '_' . $game["Team"] . '_' .$key;
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
                    'game_key' => $game["GameKey"],
                    'status' => "FINISHED",
                    'overtime' => ($game["ScoreOvertime"] > 0 || $game["OpponentScoreOvertime"] > 0) ? true : false
                 ]);
                }

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

                $slate1->games()->sync([$game_id], false);
                if($dayOfWeek == "Sunday"){
                    $slate2->games()->sync([$game_id], false);
                    $slate3->games()->sync([$game_id], false);
                    $slate4->games()->sync([$game_id], false);
                }
                else if($dayOfWeek == "Monday"){
                    $slate4->games()->sync([$game_id], false);
                    $slate5->games()->sync([$game_id], false);
                }
                else if($dayOfWeek == "Thusday"){
                    $slate2->games()->sync([$game_id], false);
                }

            }

        }

        $slates = Slate::where('active', true)->where('status', 'HISTORY')->get();
        foreach ($slates as $slate){
            $slate->firstGame = $slate->firstGameDate();
            $slate->lastGame = $slate->lastGameDate();
            $slate->save();
            $this->pullPlayerStats($slate->games()->get(), $slate->id);
            // $this->pullDefenseStats($slate->games()->get());
        }
    }

    public function addPlayers($games){
    
        foreach ($games as $game){

            $game_id = $game['id'];
            foreach ($fantasy_players as $fp){
                if($fp["team"] === $game["homeTeam"] || $fp["team"] === $game["awayTeam"]){
                    FantasyPlayer::updateOrCreate(
                        array(
                            "id" => mt_rand(1000000000, mt_getrandmax()),
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

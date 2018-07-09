<?php

namespace App\Http\Controllers\Api\v1;

use App\Common\Consts\User\UserBalanceConsts;
use App\Common\Consts\User\UserStatusConsts;
use App\Helpers\CoinbaseHelper;
use App\Http\HttpMessage;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\Requests;
use JWTAuth;
use App\Http\Controllers\Controller;
use App\User;
use App\Game;
use App\Invoice;
use JWTAuthException;
use Mockery\Exception;
use Validator;
use App\Helpers\BitPayHelper;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Auth\ForgotPasswordController;
use GuzzleHttp\Client as HttpClient;
use App\PromoCode;
use DB;
use App\Entry;
use App\Ranking_week;
use App\Ranking_month;
use Carbon\Carbon;
use Illuminate\Support\Collection;

use Illuminate\Support\Facades\Mail;
use App\Mail\RankingUpdateMail;
use Pusher\Pusher;
class UsersController extends Controller
{
    private $user;
    public function __construct(User $user){
        $this->user = $user;
    }

    public function register(Request $request)
    {

      
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'username' => 'required|min:6',
            'password' => 'required|min:6',
            'promocode' => 'nullable'
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_CREATING, $validator->errors()->all());
        }

        if (!$request->get('promocode')) {
             $user = User::where('email', $request->get('email'))->first();
        } else {
            $user = User::where('email', $request->get('email'))->where('role', '=', 'member')->first();
        }

        
        if ($user) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_EXISTS, HttpMessage::$USER_EMAIL_EXISTS,
                HttpMessage::$USER_EMAIL_EXISTS);
        }

        $user = User::where('username', $request->get('username'))->first();
        if (!$request->get('promocode') && $user) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_EXISTS, HttpMessage::$USER_USERNAME_EXISTS,
                HttpMessage::$USER_USERNAME_EXISTS);
        }

        if (!$request->get('promocode')) {
        } else {
            $promoCodeChecking = PromoCode::where('email', $request->get('email'))->first();

            if ($promoCodeChecking) {
                if ($request->get('promocode') != $promoCodeChecking->code) {
                    return HttpResponse::serverError(HttpStatus::$ERROR_PROMOCODE_INVALID, HttpMessage::$USER_PROMOCODE_INVALID,
                    HttpMessage::$USER_PROMOCODE_INVALID);
                }
            } else {
                return HttpResponse::serverError(HttpStatus::$ERROR_PROMOCODE_INVALID, HttpMessage::$USER_PERMISSION_INVALID,
                    HttpMessage::$USER_PERMISSION_INVALID);
            }

            $date = new \DateTime();
            if ($promoCodeChecking->expired < $date->getTimestamp()) {
               return HttpResponse::serverError(HttpStatus::$ERROR_PROMOCODE_INVALID, HttpMessage::$USER_PROMOCODE_EXPIRED,
                    HttpMessage::$USER_PROMOCODE_EXPIRED);
            }
        }
        


        try {

            $user = User::updateOrCreate(array('email'=>$request->get('email')),
                ['name' => $request->get('name'),
                'email' => $request->get('email'),
                'password' => bcrypt($request->get('password')),
                'username' => $request->get('username'),
                'balance' => 0,
                'role' => !$request->get('promocode') ? 'user' : 'member'
                ]
            );

            $token = null;
            $credentials = $request->only('email', 'password');
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return HttpResponse::unauthorized(HttpStatus::$ERR_USER_INVALID_CREDENTIALS,
                        HttpMessage::$USER_INVALID_CREDENTIALS, HttpMessage::$USER_INVALID_CREDENTIALS);
                }
            }
            catch (JWTAuthException $e) {
                return HttpResponse::serverError(HttpStatus::$ERR_USER_CREATE_TOKEN,
                    HttpMessage::$USER_ERR_CREATING_TOKEN, HttpMessage::$USER_ERR_CREATING_TOKEN);
            }


            // try {
            //     $client = new HttpClient(['headers' => ['Authorization' => "Basic ZHJhZnRtYXRjaDpxMTNZIE5uQ3EgSGtETSB4VVRDIDVjbHIgNFBHaw=="]]);

            //     $client->request('POST', 'https://draftmatch.com/wp-json/wp/v2/users',
            //         ['json' => ['username' => $request->get('username'), 'password' => $request->get('password'), 'email' => $request->get('email')]]);
            // }
            // catch (\GuzzleHttp\Exception\ServerException $exception){
            //     print_r('Exception');
            // }
            // catch (Exception $exception){

            // }

            $user->token = $token;
            return HttpResponse::ok(HttpMessage::$USER_CREATED_SUCCESSFULLY, $user);
        }
        catch (QueryException $e){
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_ERROR_CREATING, $e->getMessage());
        }
        catch (Exception $exception){
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_ERROR_CREATING, $e->getMessage());
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $token = null;
        try {
            if (!$token = JWTAuth::attempt($credentials, ['id'])) {
                return HttpResponse::unauthorized(HttpStatus::$ERR_USER_INVALID_CREDENTIALS,
                    HttpMessage::$USER_INVALID_CREDENTIALS,  HttpMessage::$USER_INVALID_CREDENTIALS);
            }
        }
        catch (JWTAuthException $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_CREATE_TOKEN, HttpMessage::$USER_ERR_CREATING_TOKEN,
                $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$USER_TOKEN_CREATED, (['token' => $token]));
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $token = null;
        try {
            if (!$token = JWTAuth::attempt($credentials, ['id'])) {
                return HttpResponse::unauthorized(HttpStatus::$ERR_USER_INVALID_CREDENTIALS,
                    HttpMessage::$USER_INVALID_CREDENTIALS,  HttpMessage::$USER_INVALID_CREDENTIALS);
            }
        }
        catch (JWTAuthException $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_CREATE_TOKEN, HttpMessage::$USER_ERR_CREATING_TOKEN,
                $e->getMessage());
        }

        $user = JWTAuth::toUser($token);
        if (!$user->isAdmin())
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_ADMIN_ACCESS,
                HttpMessage::$AUTH_ADMIN_ACCESS, HttpMessage::$AUTH_ADMIN_ACCESS);
        }

        return HttpResponse::ok(HttpMessage::$USER_TOKEN_CREATED, (['token' => $token]));
    }

    public function getUser(Request $request)
    {
        try {
            $user = JWTAuth::toUser($request->token);
            $user->balance = round($user->balance * 1.0/CoinbaseHelper::getExchangeRate(), 2);
        }
        catch (Exception $e){
            return HttpResponse::badRequest(HttpStatus::$ENTITY_NOT_FOUND, HttpMessage::$USER_NOT_FOUND, $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$USER_FOUND, $user);
    }

    public function getUsers(Request $request)
    {
        $userName = null;
        $status = null;
        if ($request->get('userName')) {
            $userName = $request->get('userName');
        }

        if ($request->get('status')) {
            if ($request->get('status') === UserStatusConsts::$ACTIVE || $request->get('status') === UserStatusConsts::$BLOCKED) {
                $status = $request->get('status');
            }
        }

        try {
            $users = User::getUsers($userName, $status);
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_ERROR_RETRIEVING, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_ERROR_RETRIEVING, $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$USER_FOUND,$users, array('rate' => CoinbaseHelper::getExchangeRate()));
    }

    public function blockUser(Request $request)
    {
        $id = null;
        if ($request->get('user_id')) {
            $id = $request->get('user_id');
        }
        else {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_BLOCK_ERROR_VALIDATE,
                HttpMessage::$USER_BLOCK_ERROR_VALIDATE);
        }

        try {
            $user = User::find($id);
            if ($user == null) {
                return HttpResponse::serverError(HttpStatus::$ERR_USER_NOT_FOUND, HttpMessage::$USER_NOT_FOUND,
                    HttpMessage::$USER_NOT_FOUND);
            }
            if ($user->isAdmin()) {
                return HttpResponse::serverError(HttpStatus::$ERR_BLOCK_ADMIN_USER, HttpMessage::$USER_BLOCK_ADMIN_ERROR,
                    HttpMessage::$USER_BLOCK_ADMIN_ERROR);
            }
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_NOT_FOUND, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_NOT_FOUND, $e->getMessage());
        }

        try {
            if ($user->status == UserStatusConsts::$ACTIVE) {
                $user->status = UserStatusConsts::$BLOCKED;
                $user->save();
            }
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_BLOCK_ERROR, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_BLOCK_ERROR, $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$USER_BLOCKED, null);
    }

    public function activateUser(Request $request)
    {
        $id = null;
        if ($request->get('user_id')) {
            $id = $request->get('user_id');
        }
        else {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ACTIVATE_ERROR_VALIDATE,
                HttpMessage::$USER_ACTIVATE_ERROR_VALIDATE);
        }

        try {
            $user = User::find($id);
            if ($user == null) {
                return HttpResponse::serverError(HttpStatus::$ERR_USER_NOT_FOUND, HttpMessage::$USER_NOT_FOUND,
                    HttpMessage::$USER_NOT_FOUND);
            }
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_NOT_FOUND,$e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_NOT_FOUND, $e->getMessage());
        }

        try {
            if ($user->status === UserStatusConsts::$BLOCKED) {
                $user->status = UserStatusConsts::$ACTIVE;
                $user->save();
            }
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_ACTIVATE_ERROR, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_ACTIVATE_ERROR, $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$USER_ACTIVATED, null);
    }

    public function deleteUser(Request $request){

        $id = null;
        if ($request->get('user_id')) {
            $id = $request->get('user_id');
        }
        else {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_DELETE_ERROR_VALIDATE,
                HttpMessage::$USER_DELETE_ERROR_VALIDATE);
        }

        try {
            $user = User::find($id);
            if ($user == null) {
                return HttpResponse::serverError(HttpStatus::$ERR_USER_NOT_FOUND, HttpMessage::$USER_NOT_FOUND,
                    HttpMessage::$USER_NOT_FOUND);
            }
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_NOT_FOUND,$e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_NOT_FOUND, $e->getMessage());
        }

        $user->delete();

        return HttpResponse::ok(HttpMessage::$USER_DELETED, null);


    }

    public function changeAccessPermission(Request $request){
        $id = null;
        if ($request->get('user_id')) {
            $id = $request->get('user_id');
        }
        
        try {
            $user = User::find($id);
            if ($user == null) {
                return HttpResponse::serverError(HttpStatus::$ERR_USER_NOT_FOUND, HttpMessage::$USER_NOT_FOUND,
                    HttpMessage::$USER_NOT_FOUND);
            }
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_NOT_FOUND,$e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_NOT_FOUND, $e->getMessage());
        }

        try {
                
                $user->blog_access = $request->get('blog_access');
                $user->save();
            
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_BLOG_ACCESS_CHANGE_ERROR, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_BLOG_ACCESS_CHANGE_ERROR, $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$USER_ACTIVATED, null);
    }

    public function addFunds(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            UserBalanceConsts::$AMOUNT => 'numeric|min:1|max:10000'
        ]);
        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_ADDING_FUNDS, $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$USER_ERROR_ADDING_FUNDS,
                $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }
        try {

             $response = CoinbaseHelper::sendRequestToUser($user, $request->get('amount'));
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_ERROR_ADDING_FUNDS, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_ADD_FUNDS,HttpMessage::$USER_ERROR_ADDING_FUNDS, $e->getMessage());
        }

        if (strpos($response, 'Error') !== false) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_ADD_FUNDS, HttpMessage::$USER_ERROR_ADDING_FUNDS, $response);
        }

        return HttpResponse::ok($response, $response);
    }

    public function withdrawFunds(Request $request )
    {
        $validator = \Validator::make($request->all(), [
            UserBalanceConsts::$AMOUNT => 'numeric|min:0|max:10000'
        ]);

        if ($request->get('amount') === 0){
            return HttpResponse::ok(HttpMessage::$USER_FUNDS_WITHDRAWED, "You successfully withdraw your money");
        }

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_WITHDRAW_FUNDS,
                $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,
                HttpMessage::$ERR_AUTH_INVALID_TOKEN_PROVIDED, $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }

        if ($user->balance * (1.0/CoinbaseHelper::getExchangeRate()) < $request->get(UserBalanceConsts::$AMOUNT))
        {
            return HttpResponse::serverError(HttpStatus::$ERR_NOT_ENOUGH_FUNDS, HttpMessage::$USER_NOT_ENOUGH_FUNDS,
                HttpMessage::$USER_NOT_ENOUGH_FUNDS);
        }
        try {
            $response = CoinbaseHelper::sendMoneyToUser($user, $request->get('amount'));
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_ERROR_WITHDRAW_FUNDS, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_WITHDRAW_FUNDS, HttpMessage::$USER_ERROR_WITHDRAW_FUNDS, $e->getMessage());
        }

        if (strpos($response, 'Error') !== false) {
            return HttpResponse::serverError(HttpMessage::$ERR_USER_WITHDRAW_FUNDS, $response);
        }

        return HttpResponse::ok($response, $response);
    }

    public function addBitcoins(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            UserBalanceConsts::$AMOUNT => 'numeric|min:10|max:10000'
        ]);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_ADDING_FUNDS, $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$USER_ERROR_ADDING_FUNDS,
                $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }

        try {
            $invoice = BitPayHelper::addInvoice($user, $request->get(UserBalanceConsts::$AMOUNT));

            if ($invoice === null){
                return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_ERROR_ADDING_FUNDS, HttpMessage::$USER_ERROR_ADDING_FUNDS);
            }
            try {

                \Mail::raw("Invoice for adding funds to draftmatch successfully successfully created and it is valid for 15 minuets. Click on the link
                 and scan barcode with your mobile bitcoin wallet to add funds:\n".$invoice->getUrl(), function ($message) use ($invoice, $user) {
                    $message->subject($invoice->getItemDesc())->to($user->email);
                });
            }
            catch (Exception $e){
                return HttpResponse::badRequest(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_ERR_CONTACT_SUPPORT, $e->getMessage());
            }
            $invoiceInfo = ["invoiceUrl" => $invoice->getUrl()];
        }
        catch (QueryException $e) {
            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$USER_ERROR_ADDING_FUNDS, $e->getMessage());
        }
        catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_ADD_FUNDS,HttpMessage::$USER_ERROR_ADDING_FUNDS, $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$USER_FUNDS_ADDED, $invoiceInfo);
    }

    private function getRandD($min, $max)
    {
        return (mt_rand ($min * 10, $max * 10)/10.0);
    }

    private function divideFloat($a, $b, $precision=3) {
        $a*=pow(10, $precision);
        $result=(int)($a / $b);
        if (strlen($result)==$precision) return '0.' . $result;
        else return preg_replace('/(\d{' . $precision . '})$/', '.\1', $result);
    }

    private  function convertToObject($array) {
        $object = new \stdClass();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = convertToObject($value);
            }
            $object->$key = $value;
        }
        return $object;
    }

    public function getRanking(Request $request){
        $validator = \Validator::make($request->all(), [
            "type"       => ['required', Rule::in(["all_time", "weekly", "monthly"])]]);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_ADDING_FUNDS, $validator->errors()->all());
        }
       //  // dd($request->input('type'));
       //  $options = array(
       //      'cluster' => 'mt1', 
       //      'encrypted' => true
       //  );
 
       // //Remember to set your credentials below.
       //  $pusher = new Pusher(
       //      '22436cb886a06e68758b',
       //      '43d7fd80ce194a54f933',
       //      '553320',
       //      $options
       //  );
        
       //  $message= "Hello User";
        
       //  //Send a message to notify channel with an event name of notify-event
       //  $pusher->trigger('notify', 'notify-event', $message); 
       //  dd('pusher test');

       // Mail::to('wanga6404@gmail.com')->send(new RankingUpdateMail());
       if ($request->input('type') == 'all_time'){
        //*******if type = All time then***********************
            $rankingData = DB::table('users')
                        ->where('history_count', '>', 0)
                        ->select('name', 'email', 'wins', 'loses', 'score', 'history_winning', 'history_count')
                        ->orderBy('score', 'desc')
                        ->get();
              // dd($rankingData);
            //*******parsing method****************
            foreach ($rankingData as $key => $value) {
                $ranking = $key + 1;;
                $name = $rankingData[$key]->name;
                $score = $rankingData[$key]->score;
                echo $ranking;
                echo $name;
                echo $score;
            }
            dd('Parsing and');
            //*************************************        
        //*****************************************************
       }
       else if ($request->input('type') == 'weekly'){
        //******if type is weekly then*******************
            $rankingData = array();
            for ($i = 1; $i < 21; $i++){
                $week = $i;
                $scoreData = DB::table('ranking_weeks')
                        ->join('users', 'users.id', '=', 'ranking_weeks.user_id')
                        ->join('entries', 'users.id', '=', 'entries.user_id')
                        ->join('games', 'games.id', '=', 'entries.game_id')
                        ->where('games.week', '=', $week)
                        ->select('users.name', 'users.email', 'ranking_weeks.score_week_'.$week.' as score_week')
                        ->orderBy('ranking_weeks.score_week_'.$week, 'desc')
                        ->get();
                $collection = collect($scoreData);
                $unique = $collection->unique('email');
                $unique->values()->all();
                array_push($rankingData, $unique);
            }
            foreach ($rankingData as $key => $value) {
                // echo $key, $value;
                $week = $key + 1;
                if (is_object($value) && ($value->count() != 0)){
                    $ranking = 0;
                    foreach ($value as $key1 => $value1) {
                        $ranking++;
                        if (is_object($value1)){
                            foreach ($value1 as $key2 => $value2) {
                                $ranking_weeks[$week][$ranking]['week'] = $week;
                                $ranking_weeks[$week][$ranking]['ranking'] = $ranking;
                                $ranking_weeks[$week][$ranking][$key2] = $value2; 
                            }
                        }
                        
                    }
                }
                else{
                    $ranking_weeks[$week] = null;
                }
            }
            // dd($ranking_weeks);
            //*******parsing method****************
            $rankingData = $ranking_weeks[5];
            if ($rankingData){
                foreach ($rankingData as $key => $value) {
                    $ranking = $rankingData[$key]['ranking'];
                    $name = $rankingData[$key]['name'];
                    $score = $rankingData[$key]['score_week'];
                    echo $ranking;
                    echo $name;
                    echo $score;
                }
            }
            else {
                echo 'There is no game!';
            }
            dd('Parsing and');
            //*************************************
        //*************************************************
       }
       else if ($request->input('type') == 'monthly'){
        //******if type is monthly then*******************
            $rankingData = array();
            for ($i = 1; $i < 13; $i++){
                $startMonth = $i;
                $dateS = new Carbon('2018-'.$startMonth.'-1');
                $dateE = new Carbon('2018-'.$startMonth.'-31');
                $scoreData = DB::table('ranking_months')
                        ->join('users', 'users.id', '=', 'ranking_months.user_id')
                        ->join('entries', 'users.id', '=', 'entries.user_id')
                        ->join('games', 'games.id', '=', 'entries.game_id')
                        ->whereBetween('games.date', [$dateS->format('Y-m-d')." 00:00:00", $dateE->format('Y-m-d')." 23:59:59"])
                        ->select('users.name', 'users.email', 'ranking_months.score_month_'.$startMonth.' as score_month')
                        ->orderBy('ranking_months.score_month_'.$startMonth, 'desc')
                        ->get();
               $collection = collect($scoreData);
               $unique = $collection->unique('email');
               $unique->values()->all();
               array_push($rankingData, $unique);
                
            }
            foreach ($rankingData as $key => $value) {
                // echo $key, $value;
                $month = $key + 1;
                if (is_object($value) && ($value->count() != 0)){
                    $ranking = 0;
                    foreach ($value as $key1 => $value1) {
                        $ranking++;
                        if (is_object($value1)){
                            foreach ($value1 as $key2 => $value2) {
                                $ranking_months[$month][$ranking]['month'] = $month;
                                $ranking_months[$month][$ranking]['ranking'] = $ranking;
                                $ranking_months[$month][$ranking][$key2] = $value2; 
                            }
                        }
                        
                    }
                }
                else{
                    $ranking_months[$month] = null;
                }
            }
            //*******parsing method****************
            $rankingData = $ranking_months[6];
            if ($rankingData){
                foreach ($rankingData as $key => $value) {
                    $ranking = $rankingData[$key]['ranking'];
                    $name = $rankingData[$key]['name'];
                    $score = $rankingData[$key]['score_month'];
                    echo $ranking;
                    echo $name;
                    echo $score;
                }
            }
            else {
                echo 'There is no game!';
            }
            dd('Parsing and');
            //*************************************
        //*************************************************
       }
       else {

       }

      return HttpResponse::ok(HttpMessage::$USER_FOUND, $rankingData);
    }
    public function setRanking(Request $request){
        $validator = \Validator::make($request->all(), [
            "type"       => ['required', Rule::in(["all_time", "weekly", "monthly"])]]);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_ADDING_FUNDS, $validator->errors()->all());
        }

        // $user = User::all()->count();
        // for ($i = 1; $i<37; $i++){
        //     $user = User::find($i);

        //     $user->history_count = round($this->getRandD(1.0, 10.0));
        //     $user->history_winning = $this->getRandD(1.0, 3.6);

        //     $user->save();
        // }
        // for ($i = 36; $i<48; $i++){
        //     $user = User::find($i);

        //     $user->history_count = round($this->getRandD(1.0, 10.0));
        //     $user->history_winning = $this->getRandD(1.0, 3.6);

        //     $user->save();
        // }
        // for ($i = 1542; $i < 1579; $i++){
        //     $entry = Entry::find($i);
        //     $entry->winning = $this -> getRandD(0.5, 1.2);
        //     $entry -> save();
        // }
        //********if type = "all_time"***********
        // $users = User::all();
        // foreach($users as $user){
        //       echo $user;
        //       if ($user['history_count'] > 0){
        //         $user['score'] = $this -> divideFloat($user['history_winning'], $user['history_count']);
        //       }
        //       else{
        //         $user['score'] = 0.0;
        //       }
        //       $user->update(['score'=>$user['score']]);
        // }
        // dd('df');
        //***************************************
        //********if type = "weekly"*************
        // $users = User::all();
        // foreach ($users as $user) {
        //     $userScore = array();
        //     for ($i = 1; $i < 21 ; $i++){
        //         $scoreData = DB::table('users')->where('users.id', '=', $user->id)
        //             ->join('entries', 'users.id', '=', 'entries.user_id')
        //             ->join('games', 'games.id', '=', 'entries.game_id')
        //             ->where('games.week', 'like', $i)
        //             ->select('users.name','games.week', 'entries.winning')
        //             ->get();
        //         $week_playCount = count($scoreData);
        //         $score = 0;
        //         foreach ($scoreData as $scoreValue) {
        //             $score += $scoreValue->winning;
        //         }
        //         if ($week_playCount > 0){
        //             $score = $this -> divideFloat($score, $week_playCount);
        //         }
        //         else{
        //             $score = 0.0;
        //         }
        //         $userscore = array_push($userScore, $score);
        //     }

        //     foreach ($userScore as $key => $value) {
        //         $week = $key + 1;
        //         $userScoreObject['score_week_'.$week] = $value;
        //     }
        //     $userScoreObject['user_id'] = $user->id;
        //     $ranking_weeks = Ranking_week::UpdateOrCreate($userScoreObject);
        // }
        //****************************************
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
        dd('dd');
        return HttpResponse::ok(HttpMessage::$USER_FOUND, $users);
    }

    public function resetPassword(Request $request){
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email']);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERR_RESET_PASSWORD, $validator->errors()->all());
        }

        $user = User::where('email', '=', $request->get('email'))->first();

        if (!$user){
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_NO_USER_WITH_EMAIL, HttpMessage::$USER_NO_USER_WITH_EMAIL);
        }

        try {
            $var = new ForgotPasswordController();

            $var->sendResetLinkEmail(new \Illuminate\Http\Request(["email" => $request->get('email')]));
        }
        catch (Exception $e){
            return HttpResponse::badRequest(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_ERR_SENDING_EMAIL, $e->getMessage());
        }

        return HttpResponse::ok(HttpMessage::$USER_MAIL_PASSWORD_SENT, null);
    }

    public function contactSupport(Request $request){
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email',
            'fullName' => 'required|min:1',
            'text' => 'required|min:1']);

        \Log::info("Trying to send email to support ...");
        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERR_CONTACT_SUPPORT, $validator->errors()->all());
        }

        $user = User::where('email', '=', $request->get('email'))->first();

        if (!$user){
            \Log::info('User with email '.$request->get('email').' not found ');
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_NO_USER_WITH_EMAIL, HttpMessage::$USER_NO_USER_WITH_EMAIL);
        }

        try {

            \Mail::raw($request->get('text'), function ($message) use ($request) {
                $message->subject('Ticket-'.time().' from user '.$request->get('fullName').' email:'.$request->get('email'))->to('support@draftmatch.com');
            });
        }
        catch (Exception $e){
            \Log::info('Error sending email. Error '.$e->getMessage());
            return HttpResponse::badRequest(HttpStatus::$ERR_UNKNOWN, HttpMessage::$USER_ERR_CONTACT_SUPPORT, $e->getMessage());
        }

        \Log::info('Email successfully sent ...');
        return HttpResponse::ok(HttpMessage::$USER_MAIL_SUPPORT_SENT, null);
    }

    public function transactions(Request $request){
        \Log::info("Trying to retrieve all transactions for user ...");

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,
                HttpMessage::$ERR_AUTH_INVALID_TOKEN_PROVIDED, $exception->getMessage());
        }

        $invoices = Invoice::where('user_id', '=', $user->id)->orderBy('createdAt', 'asc')->get();
        \Log::info('Successfully retrieved user transactions ...');
        return HttpResponse::ok(HttpMessage::$USER_TRANSACTIONS_RECEIVED, $invoices);
    }

    public function addFundsByCheckbook(Request $request){
        $validator = \Validator::make($request->all(), [
            UserBalanceConsts::$AMOUNT => 'numeric|min:1|max:10000'
        ]);
        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_ADDING_FUNDS, $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$USER_ERROR_ADDING_FUNDS,
                $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }

        // $client = new HttpClient(['headers' => ['Authorization' => "64b4d2f7475f4c7aa5bb1bc08d1b58ee:jNYQNhHCm5Pm7tfBausN7FLAzxiQfF",
        //     'Content-Type' => 'application/json'
        //     ]
        // ]);
        // $url = 'https://checkbook.io/v3/invoice';

        $client = new HttpClient(['headers' => ['Authorization' => "0a7990396d731af2d7802805b1c573ed:bdb71b58f24f853c6f60f7a03951e9b5",
            'Content-Type' => 'application/json'
            ]
        ]);
        $url = 'https://sandbox.checkbook.io/v3/invoice';


        $test = $client->request('POST', $url, ['json' => array('name' => 'DraftMatch LLC',
            'recipient' => 'phanthanhhung1118@gmail.com',
            'amount' => 1.00,
            'description' => 'Invoice 125'
        )])->getBody();

        echo $test;

        // return redirect()->action('Api\v1\UsersController@checkbookCallback');

    }

    public function withdrawFundsByCheckbook(Request $request){

        $validator = \Validator::make($request->all(), [
            UserBalanceConsts::$AMOUNT => 'numeric|min:1|max:10000'
        ]);
        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_ADDING_FUNDS, $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$USER_ERROR_ADDING_FUNDS,
                $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }

        $client = new HttpClient(['headers' => ['Authorization' => "0a7990396d731af2d7802805b1c573ed:bdb71b58f24f853c6f60f7a03951e9b5",
            'Content-Type' => 'application/json'
            ]
        ]);
        $url = 'https://sandbox.checkbook.io/v3/check/digital';


        $test = $client->request('POST', $url, ['json' => array('name' => 'Softman',
            'recipient' => 'softman009@outlook.com',
            'amount' => 2.00,
            'description' => 'Invoice 126'
        )])->getBody();

        echo $test;


    }

    public function checkbookCallback(Request $request){
        
       

        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => '7b141d43ffe04621ab67de46d4360a05',    
            'clientSecret'            => 'bdb71b58f24f853c6f60f7a03951e9b5',  
            'redirectUri'             => 'http://127.0.0.1:8000/api/v1/checkbook/callback',
            'urlAuthorize'            => 'https://sandbox.checkbook.io/oauth/authorize',
            'urlAccessToken'          => 'https://sandbox.checkbook.io/oauth/token',
            'urlResourceOwnerDetails' => 'https://sandbox.checkbook.io/oauth/resource'
        ]);

        // If we don't have an authorization code then get one
        if (!isset($_GET['code'])) {
        
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl();
        
            // Get the state generated for you and store it to the session.
            $_SESSION['oauth2state'] = $provider->getState();
        
            // Redirect the user to the authorization URL.
            header('Location: ' . $authorizationUrl);
            echo "step one";
            exit;
        
        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['       oauth2state'])) {
        
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }

            echo "step two";
            exit('Invalid state');
        
        } else {
        
            try {
        
                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
        
                // We have an access token, which we may use in authenticated
                // requests against the service provider's API.
                echo 'Access Token: ' . $accessToken->getToken() . "<br>";
                echo 'Refresh Token: ' . $accessToken->getRefreshToken() . "<br>";
                echo 'Expired in: ' . $accessToken->getExpires() . "<br>";
                echo 'Already expired? ' . ($accessToken->hasExpired() ? 'expired' : 'not expired') . "<br>";
        
               
        
            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        
                // Failed to get the access token or user details.
                exit($e->getMessage());
        
            }
        
        }       
            
    }

}
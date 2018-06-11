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
use App\Invoice;
use JWTAuthException;
use Mockery\Exception;
use Validator;
use App\Helpers\BitPayHelper;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Auth\ForgotPasswordController;
use GuzzleHttp\Client as HttpClient;
use App\PromoCode;

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
            'promocode' => 'required'
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_CREATING, $validator->errors()->all());
        }

        $user = User::where('email', $request->get('email'))->first();
        if ($user) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_EXISTS, HttpMessage::$USER_EMAIL_EXISTS,
                HttpMessage::$USER_EMAIL_EXISTS);
        }

        $user = User::where('username', $request->get('username'))->first();
        if ($user) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_EXISTS, HttpMessage::$USER_USERNAME_EXISTS,
                HttpMessage::$USER_USERNAME_EXISTS);
        }

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


        try {
            $user = $this->user->create([
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'password' => bcrypt($request->get('password')),
                'username' => $request->get('username'),
                'balance' => 0
            ]);

            $user->balance = 0;
            $user->save();

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


            try {
                $client = new HttpClient(['headers' => ['Authorization' => "Basic ZHJhZnRtYXRjaDpxMTNZIE5uQ3EgSGtETSB4VVRDIDVjbHIgNFBHaw=="]]);

                $client->request('POST', 'https://draftmatch.com/wp-json/wp/v2/users',
                    ['json' => ['username' => $request->get('username'), 'password' => $request->get('password'), 'email' => $request->get('email')]]);
            }
            catch (\GuzzleHttp\Exception\ServerException $exception){
                print_r('Exception');
            }
            catch (Exception $exception){

            }

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

        return HttpResponse::ok(HttpMessage::$USER_FOUND, $users);
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

    public function getRanking(Request $request){
        $validator = \Validator::make($request->all(), [
            "type"       => ['required', Rule::in(["all_time", "weekly", "monthly"])]]);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_ADDING_FUNDS, $validator->errors()->all());
        }

        $users = User::getAllUsers();
        foreach($users as $user){
            $user["pts"] = $this->getRandD(1.0, 1000.0);
        }

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
}
<?php

namespace App\Http\Controllers\Admin\v1;

use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Mail\PromoCodeMail;
use Illuminate\Http\Request;
use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\HttpMessage;
use Mockery\Exception;
use App\PromoCode;
use App\User;

class MailController extends Controller
{

	public function __construct()
    {
      
    }

    
    public function index()
    {
      
    }

    public function sendPromoCode(Request $request)
    {

      $validator = \Validator::make($request->all(), [
            'email'    => 'required',
            'invitedLevel' => 'required'
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERROR_EMAIL_NOT_PROVIDED, HttpMessage::$USER_PROMOCODE_ERROR_VALIDATE,
                $validator->errors()->all());
        }

        $user_email = User::where('email', $request->get('email'))->where('role', '=', 'member')->first();
        if ($user_email) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_EXISTS, HttpMessage::$USER_EMAIL_EXISTS,
                HttpMessage::$USER_EMAIL_EXISTS);
        }

        $invitedLevel = $request->get('invitedLevel');

      $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
      $promocode = "";
      for ($i = 0; $i < 10; $i++) {
          $promocode .= $chars[mt_rand(0, strlen($chars)-1)];
      }
      if ($invitedLevel == 10){
          $promocode = $promocode."DM10GAME";
      }
      else{
          $promocode = $promocode."DM20PLAY";
      }
      $date = new \DateTime();
      $expireDate = $date->getTimestamp() + 86400 * 3;
      
      try {
                
           Mail::to($request->get('email'))->send(new PromoCodeMail($promocode, $expireDate));

       } catch (\Exception $e) {
           return HttpResponse::serverError(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_INVALID_EMAIL_FORMAT,
                HttpMessage::$USER_INVALID_EMAIL_FORMAT);
       }

      
      PromoCode::UpdateOrCreate(
                            ['email' => $request->get('email')],
                            ['email' => $request->get('email'),
                             'code'  => $promocode,
                             'expired'=> $expireDate
                            ]
                        );


     return HttpResponse::ok(HttpMessage::$USER_PROMOCODE_SUCCESS, null);
    }
    
   
}

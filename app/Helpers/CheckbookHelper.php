<?php

namespace App\Helpers;

use App\Check;
use Mockery\Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client as HttpClient;
use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\HttpMessage;
use App\Helpers\CoinbaseHelper;

class CheckbookHelper {

	public static function sendMoneyToUser($user, $amount){
		 /*
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
        */
        $retMessage = "";

        try {

            $client = new HttpClient(['headers' => ['Authorization' => "0a7990396d731af2d7802805b1c573ed:bdb71b58f24f853c6f60f7a03951e9b5",
                'Content-Type' => 'application/json'
                ]
            ]);
            $url = env('CHECKBOOK_URL', 'https://checkbook.io').'/v3/check/digital';


            $response = json_decode($client->request('POST', $url, ['json' => array('name' => 'DraftMatch LLC',
                'recipient' => $user->email,
                'amount' => $amount*1.0,
                'description' => 'Check ID: '.time().'_'.($user->id).'_'.($amount).' Sending '.$amount.'$ to user '.$user->email
            )])->getBody()->getContents(), true);

            try {
                $check = new Check();
                $check->email = $user->email;
                $check->user_id = $user->id;
                $check->amount = $amount*1.0;
                $check->description = $response['description'];
                $check->status = $response['status'];
                $check->image_uri = $response['image_uri'];
                $check->gen_time = $response['date'];
                $check->checkid = $response['id'];
                $check->type = 'OUTGOING';
                $check->checked = false;

                $check->save();
                $retMessage = "You successfully requested withdraw fund from DraftMatch.";
            }catch(Exception $e){
                \Log::info('Error saving check id in database ...');
                $retMessage = "Error saving check id in database ...";
            }

            if ($response['status'] === 'IN_PROCESS') {
                $user->balance = $user->balance - $amount / 1.0 * CoinbaseHelper::getExchangeRate();
                $user->save();

                $check->checked = true;
                $check->save();
            }

            return $retMessage;
        }catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_ADD_FUNDS,HttpMessage::$USER_ERROR_ADDING_FUNDS, $e->getMessage());
        }

	}

	public static function sendRequestToUser($user, $amount){

		/* email funding
        $client = new HttpClient(['headers' => ['Authorization' => "64b4d2f7475f4c7aa5bb1bc08d1b58ee:jNYQNhHCm5Pm7tfBausN7FLAzxiQfF",
            'Content-Type' => 'application/json'
            ]
        ]);
        $url = 'https://checkbook.io/v3/invoice';

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

        return \Redirect::route('checkbookcallback');
        */

        $retMessage = "";

        try {

        		$client = new HttpClient(['headers' => ['Authorization' => "bearer ".$user->token,
        		    'Content-Type' => 'application/json'
        		    ]
        		]);
        		$url = env('CHECKBOOK_URL', 'https://checkbook.io').'/v3/check/digital';


        		$response = json_decode($client->request('POST', $url, ['json' => array('name' => 'DraftMatch LLC',
        		    'recipient' => 'admin@draftmatch.com',
        		    'amount' => $amount*1.0,
        		    'description' => 'Check ID: '.time().'_'.($user->id).'_'.($amount).' Receiving '.$amount.'$ from user '.$user->email
        		)])->getBody()->getContents(), true);


        		try {
                     $check = new Check();
                     $check->email = $user->email;
                     $check->user_id = $user->id;
                     $check->amount = $amount*1.0;
                     $check->description = $response['description'];
                     $check->status = $response['status'];
                     $check->image_uri = $response['image_uri'];
                     $check->gen_time = $response['date'];
                     $check->checkid = $response['id'];
                     $check->type = 'INCOMING';
                     $check->checked = false;

                     $check->save();
                     $retMessage = "You successfully requested adding fund to DraftMatch from your account.";
                 }catch(Exception $e){
                     \Log::info('Error saving check id in database ...');
                     $retMessage = "Error saving check id in database ...";
                 }

                 if ($response['status'] === 'IN_PROCESS') {
                     $user->balance = $user->balance + $amount / 1.0 * CoinbaseHelper::getExchangeRate();
                     $user->save();

                     $check->checked = true;
                     $check->save();
                 }

                 return $retMessage;
		    }catch (Exception $e) {
                 return HttpResponse::serverError(HttpStatus::$ERR_USER_ADD_FUNDS,HttpMessage::$USER_ERROR_ADDING_FUNDS, $e->getMessage());
        }
	}


}
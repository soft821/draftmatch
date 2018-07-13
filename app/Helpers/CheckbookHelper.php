<?php

namespace App\Helpers;

use App\Invoice;
use Mockery\Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client as HttpClient;
use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\HttpMessage;

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

        try {

            $client = new HttpClient(['headers' => ['Authorization' => "0a7990396d731af2d7802805b1c573ed:bdb71b58f24f853c6f60f7a03951e9b5",
                'Content-Type' => 'application/json'
                ]
            ]);
            $url = 'https://sandbox.checkbook.io/v3/check/digital';


            $response = $client->request('POST', $url, ['json' => array('name' => 'DraftMatch LLC',
                'recipient' => $user->email,
                'amount' => $amount*1.0,
                'description' => 'withdraw funds from DraftMatch.com'
            )])->getBody();


            return $response;
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

        try {

        		$client = new HttpClient(['headers' => ['Authorization' => "bearer ".$user->token,
        		    'Content-Type' => 'application/json'
        		    ]
        		]);
        		$url = 'https://sandbox.checkbook.io/v3/check/digital';


        		$response = $client->request('POST', $url, ['json' => array('name' => 'DraftMatch LLC',
        		    'recipient' => 'admin@draftmatch.com',
        		    'amount' => $amount*1.0,
        		    'description' => 'Add funds to DraftMatch.com'
        		)])->getBody();


        		return $response;
		    }catch (Exception $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_ADD_FUNDS,HttpMessage::$USER_ERROR_ADDING_FUNDS, $e->getMessage());
        }
	}	
}
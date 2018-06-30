<?php
namespace App\Http\Controllers\Admin\fake;

use App\BitCoinInfo;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Http\Request;

class BitPaySetController extends Controller
{

	public function index()
    {
        
    }

    public function getTokensForMerchant(Request $request)
    {
    	try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,
                HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED, $exception->getMessage());
        }

        if (!$user->isAdmin(){
			return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,
                HttpMessage::$AUTH_INVALID_TOKEN_PROVIDED, $exception->getMessage());
        }

		$client = new HttpClient();
        $res = $client->request('POST', 
        	"https://bitpay.com/tokens?".
        	"label=outlook account&".
        	"id="
    	)->getBody();
        $resBody = json_decode($res);

        echo $resBody;


    }
}
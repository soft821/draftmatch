<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\FantasyPlayer;
use Illuminate\Console\Command;
use App\Slate;
use App\Game;
use App\BitCoinInfo;
use GuzzleHttp\Client as HttpClient;

use App\Contest;
use App\Common\Consts\Contest\ContestStatusConsts;
use JWTAuth;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\HttpMessage;
use Mockery\Exception;
use App\Helpers\CoinbaseHelper;

use FacebookAds\Object\AdSet;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Object\AdAccount;
use FacebookAds\Api;
use FacebookAds\Object\Fields\AdAccountFields;

class HomeController extends Controller
{

public function __construct()
{
    //$this->middleware('auth');
}



public function test(){
 //    $account_id = 'act_2530402310319174';
	// $campaign_id = '12345642434';

	// $app_id = '684973041845313';
	// $app_secret = '47a83806ccf028d6fe2ec4240761e476';
	// $access_token = 'EAAJuZBrbXBEEBAINDaTb4lUp85bYBTHFjCiNlZAdVIY0n1hvFcZBZCK6k0Ej9rlmcmuOEFuau94GkUegW3z6qvuVJBSOmAT5LMOW5FltPmVRgNb0iDCgHy3kjd35NZBK3745RQTH3sfNsrBJjvSlPplFXajzOWMLU35ULgrbdZCFq9RhZBcnKGBHMux5d2JFZCjNVekoBIzc8QZDZD';
	// // Initialize a new Session and instantiate an Api object
	// Api::init($app_id, $app_secret, $access_token);

	// // The Api object is now available trough singleton
	// $api = Api::instance();

	// $fields = array(
	//   AdAccountFields::ID,
	//   AdAccountFields::NAME
	// );

	// $account = new AdAccount($account_id);
	// $account = $account->read($fields);

	// $set = new AdSet(null, $account_id);
	// $set->setData(array(
	//   AdSetFields::NAME => 'My Test AdSet',
	//   // AdSetFields::CAMPAIGN_ID => $campaign_id,
	//   AdSetFields::DAILY_BUDGET => 150,
	//   AdSetFields::START_TIME => (new \DateTime("+1 week"))->format(\DateTime::ISO8601),
	//   AdSetFields::END_TIME => (new \DateTime("+2 week"))->format(\DateTime::ISO8601),
	// ));
	// $set->create(array(
	//   AdSet::STATUS_PARAM_NAME => AdSet::STATUS_PAUSED,
	// ));
	// dd('df');
	// echo $set->id;
	return view('facebook');

}




public function index()
{
    return redirect('http://draftmatch.com');
}

    
}

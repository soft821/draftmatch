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

class HomeController extends Controller
{

public function __construct()
{
    //$this->middleware('auth');
}



public function test(){
    $account_id = 'act_123123';
	$campaign_id = '123456';

	$app_id = '2076694199317059';
	$app_secret = '3f3c2778e726489b9b11ed73cc1ab24c';
	$access_token = ;
	// Initialize a new Session and instantiate an Api object
	Api::init($app_id, $app_secret, $access_token);

	// The Api object is now available trough singleton
	$api = Api::instance();

	$account = new AdAccount();
	$account->name = 'My account name';
	echo $account->name;

	$set = new AdSet(null, $account_id);
	$set->setData(array(
	  AdSetFields::NAME => 'My Test AdSet',
	  AdSetFields::CAMPAIGN_ID => $campaign_id,
	  AdSetFields::DAILY_BUDGET => 150,
	  AdSetFields::START_TIME => (new \DateTime("+1 week"))->format(\DateTime::ISO8601),
	  AdSetFields::END_TIME => (new \DateTime("+2 week"))->format(\DateTime::ISO8601),
	));
	$set->create(array(
	  AdSet::STATUS_PARAM_NAME => AdSet::STATUS_PAUSED,
	));
	echo $set->id;

}




public function index()
{
    return redirect('http://draftmatch.com');
}

    
}

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

class HomeController extends Controller
{

public function __construct()
{
    //$this->middleware('auth');
}



public function test(){
    echo BitCoinInfo::getInfo();

}




public function index()
{
    return redirect('http://draftmatch.com');
}

    
}

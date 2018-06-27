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

<<<<<<< HEAD
    public function test(){
        $adminEmail = 'fdsa';
        \Mail::send('emails.admin_invoices', ['text' => 'Pending request for ' . '($amount)' . '$ for DraftMatch sent to user .',
                                'header' => 'DraftMatch Deposit Pending'], function ($message) use ($adminEmail)
                            {
                                $message->subject('DraftMatch Deposit Pending');

                                $message->to('jingzhang009@gmail.com');
                            });
=======
    public function test(Request $request){

>>>>>>> jon
    }

    public function index()
    {
        return redirect('http://draftmatch.com');
    }
    
}

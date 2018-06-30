<?php
namespace App\Http\Controllers\Admin\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebHookController extends Controller
{

	public function __construct()
    {
      
    }

    
    public function index()
    {
      
    }

    public function getInvoiceCoinbase(Request $request)
    {

    	error_log('callback notification. '.json_encode($request->all()));
    	\Mail::raw('callback notification. '.json_encode($request->all()), function ($message)  {
                   $message->subject('DraftMatch Withdraw Successful')->to('jingzhang009@gmail.com');
               });

    	return;
    }

}
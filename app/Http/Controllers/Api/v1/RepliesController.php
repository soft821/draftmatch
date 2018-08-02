<?php

namespace App\Http\Controllers\Api\v1;
use App\Thread;
use App\Reply;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Validator;
use JWTAuth;
use App\Http\HttpMessage;
use Illuminate\Database\QueryException;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\Requests;
use JWTAuthException;
use Mockery\Exception;
use DB;
use App\Common\Consts\User\UserStatusConsts;

class RepliesController extends Controller
{
	public function __construct()
	{
		// $this->middleware('auth', ['except' => 'index']);
	}

    public function index($thread_id)
    {
        $thread_id = $thread_id;
        $thread = Thread::find($thread_id);
        $replies = $thread->replies()->paginate(20);
        return HttpResponse::ok(HttpMessage::$REPLY_RETIRIVED, $replies);
    }
	
    public function store($thread_id, Request $request)
    {
        $validator = \Validator::make($request->all(), [
             'body' => 'required'
            ]);
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$FORUM_ERROR_VALIDATING_REPLY, $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$FORUM_ERROR_CREAING_REPLY,
                $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }
        $thread_id = $thread_id;
        $thread = Thread::find($thread_id);
        $reply = $thread->addReply([
        	'body' => request('body'),
        	'user_id' => $user->id
        ]);

        return HttpResponse::ok(HttpMessage::$REPLY_CREATED, $reply);
    }

    public function update($reply_id, Request $request)
    {
        $validator = \Validator::make($request->all(), [
             'body' => 'required'
            ]);
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$FORUM_ERROR_VALIDATING_REPLY, $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$FORUM_ERROR_UPDAING_REPLY,
                $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }

        $reply_id = $reply_id;
        $reply = Reply::find($reply_id);
        if ($reply->user_id != $user->id)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_UPDATE_REPLY, HttpMessage::$FORUM_ERROR_UPDATING_OWN_REPLY,
                    HttpMessage::$FORUM_ERROR_UPDATING_OWN_REPLY);
        }
        $reply->body = $request->get('body');
        $reply->save();
        return HttpResponse::ok(HttpMessage::$REPLY_UPDATED, $reply);

    }

    public function destroy($reply_id, Request $request){

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$FORUM_ERROR_UPDAING_REPLY,
                $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }

        $reply_id = $reply_id;
        $reply = Reply::find($reply_id);
        if ($reply->user_id != $user->id)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_UPDATE_REPLY, HttpMessage::$FORUM_ERROR_UPDATING_OWN_REPLY,
                    HttpMessage::$FORUM_ERROR_UPDATING_OWN_REPLY);
        }
        $reply->body = $request->get('body');
        $reply->save();
        return HttpResponse::ok(HttpMessage::$REPLY_UPDATED, $reply);
    }
}

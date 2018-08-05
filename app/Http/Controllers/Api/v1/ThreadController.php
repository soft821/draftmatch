<?php

namespace App\Http\Controllers\Api\v1;

use App\Thread;
use App\Channel;
use App\Filters\ThreadFilters;
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
class ThreadController extends Controller
{

    /**
     * Threads constructor
     */
    public function __construct()
    {
        // $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Channel $channel, ThreadFilters $filters)
    {

        $threads = $this->getThreads($channel, $filters);

        

        return HttpResponse::ok(HttpMessage::$THREAD_RETRIVIED, $threads);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
            $channels = Channel::get();
            return HttpResponse::ok(HttpMessage::$RETRIEVING_CHANNELS, $channels);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'title' => 'required',
            'body' => 'required',
            'channel' => 'required' // laravel validation helpers
            ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$FORUM_ERROR_VALIDATING_THREAD, $validator->errors()->all());
        }

        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$FORUM_ERROR_CREAING_THREAD,
                $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }

        if ($user->thread_access === UserStatusConsts::$THREAD_ACCESS_DEACTIVE)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_DISABLED_THREAD_ACCESS, HttpMessage::$USER_DISABLED_THREAD_ACCESS,
                HttpMessage::$USER_DISABLED_THREAD_ACCESS);
        }

        $thread = Thread::UpdateOrCreate([
            'user_id' => $user->id,
            'channel_id' => $request->get('channel'),
            'title' => $request->get('title'),
            'body' => $request->get('body'),
            'lock_status' => false
        ]);

        return HttpResponse::ok(HttpMessage::$THREAD_CREATED, $thread);
       
    }

    /**
     * Display the specified resource.
     */
    public function show($thread_id)
    {
        $threads = Thread::where('id', $thread_id)->get();
        return HttpResponse::ok(HttpMessage::$THREAD_RETRIVIED, $threads);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Thread $thread)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Thread $thread)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($thread_id, Request $request)
    {
        // check that it is signed in user's thread
        // https://laravel.com/docs/5.4/authorization#via-controller-helpers
        try {
            $user = JWTAuth::toUser($request->token);
        }
        catch (Exception $exception)
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$FORUM_ERROR_DELEING_THREAD,
                $exception->getMessage());
        }

        if ($user->status === UserStatusConsts::$BLOCKED)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
                HttpMessage::$USER_BLOCKED_OPERATION);
        }

        if ($user->thread_access === UserStatusConsts::$THREAD_ACCESS_DEACTIVE)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_DISABLED_THREAD_ACCESS, HttpMessage::$USER_DISABLED_THREAD_ACCESS,
                HttpMessage::$USER_DISABLED_THREAD_ACCESS);
        }
        // dd($thread_id);
        $thread_id = $thread_id;
        $thread = Thread::find($thread_id);
        if ($thread->user_id != $user->id)
        {
            return HttpResponse::serverError(HttpStatus::$ERR_DELETE_THREAD, HttpMessage::$FORUM_ERROR_DELETING_OWN_THREAD,
                    HttpMessage::$FORUM_ERROR_DELETING_OWN_THREAD);
        }

        $thread->delete();

        return HttpResponse::ok(HttpMessage::$THREAD_DELETED, $thread);
    }

    // Fetch all relevant threads
    public function getThreads(Channel $channel, ThreadFilters $filters)
    {
        $threads = Thread::latest()->filter($filters);
        if($channel->exists){
            $threads->where('channel_id', $channel->id);
        }

        return $threads->get();
    }
}

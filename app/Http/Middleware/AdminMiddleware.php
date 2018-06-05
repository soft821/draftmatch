<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use App\Http\HttpMessage;
use App\Http\HttpResponse;
use App\Http\HttpStatus;

class AdminMiddleware extends VerifyJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $response =  parent::handle($request, $next);

        $header = $request->header('Authorization');
        $pos = strpos($header,$this->baerer);
        $baererAuth = explode(' ', $header);
        $user = JWTAuth::toUser($baererAuth[1]);

        if (!$user->isAdmin())
        {
            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_ADMIN_ACCESS,
                HttpMessage::$AUTH_ADMIN_ACCESS, HttpMessage::$AUTH_ADMIN_ACCESS);
        }

        return $response;
    }
}

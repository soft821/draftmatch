<?php

namespace App\Http\Middleware;

use App\Helpers\LocationHelper;
use Closure;
use App\Http\HttpMessage;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\Requests;

class CheckLocation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $validator = \Validator::make($request->all(), [
            'lang' => 'required|numeric',
            'lat' => 'required|numeric']);

        // if any of validation rules failed, we will fail to create contest
        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$AUTH_INVALID_LOCATION, $validator->errors()->all());
        }

        if (!LocationHelper::isUserInAllowedLocation($request->get('lang'), $request->get('lat'))) {
            return HttpResponse::forbidden(HttpStatus::$ERR_AUTH_USER_NOT_ALLOWED, HttpMessage::$AUTH_FORBIDDEN_LOCATION, HttpMessage::$AUTH_FORBIDDEN_LOCATION);
        }

        return $next($request);
    }
}

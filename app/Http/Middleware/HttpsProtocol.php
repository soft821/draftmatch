<?php
/**
 * Created by PhpStorm.
 * User: hariso
 * Date: 31/08/2017
 * Time: 15:29
 */

namespace App\Http\Middleware;

use Closure;

class HttpsProtocol
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
        if (!$request->secure() && env('APP_ENV') === 'prod') {
            $request->setTrustedProxies( [ $request->getClientIp() ] );
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}

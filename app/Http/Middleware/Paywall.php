<?php

namespace App\Http\Middleware;

use Closure;
class Paywall extends Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if ($user = auth()->guard('api')->user()) {
            if (!$user->test_user && !$user->latest_payment_id) {
                return response()->json(['error'=>403, 'message' => 'Not subscribed'], 400);
            }
        }
        return $next($request);
    }
}

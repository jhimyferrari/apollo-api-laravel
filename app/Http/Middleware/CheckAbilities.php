<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities as SanctumCheckAbilities;
use Symfony\Component\HttpFoundation\Response;

class CheckAbilities extends SanctumCheckAbilities
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, $next, ...$abilities)
    {
        if (! $request->user() || ! $request->user()->currentAccessToken()) {
            abort(404);
        }

        foreach ($abilities as $ability) {
            if (! $request->user()->tokenCan($ability)) {
                abort(404);
            }
        }

        return $next($request);
    }
}

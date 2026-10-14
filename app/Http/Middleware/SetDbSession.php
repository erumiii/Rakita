<?php

namespace App\Http\Middleware;

use App\Support\DbSession;
use Closure;
use Illuminate\Http\Request;

class SetDbSession
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            DbSession::apply($user);
        }

        return $next($request);
    }
}

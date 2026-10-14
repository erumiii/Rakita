<?php

namespace App\Http\Middleware;

use App\Support\DbSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetDbSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Auth lookup runs on the superuser connection (pre-context), so it
        // still sees a deactivated row — re-check the flag here and end the
        // session; the next request re-authenticates cleanly.
        if ($user->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login');
        }

        DbSession::apply($user);

        return $next($request);
    }
}

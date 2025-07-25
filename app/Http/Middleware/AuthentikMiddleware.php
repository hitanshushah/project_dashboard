<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AuthentikMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $username = $request->header('x-authentik-username');
        $name = $request->header('x-authentik-name');
        $email = $request->header('x-authentik-email');

        $logoutUrl = env('APP_URL') . env('AUTHENTIK_LOGOUT_URL');

        if (!$username) {
            return redirect($logoutUrl)->withErrors(['authentik' => 'Missing Authentik-Username header.']);
        }

        $user = User::firstOrCreate(
            ['username' => $username],
            [
                'name' => $name ?? '',
                'email' => $email ?? '',
            ]
        );

        $request->attributes->set('user', $user);

        return $next($request);
    }
}

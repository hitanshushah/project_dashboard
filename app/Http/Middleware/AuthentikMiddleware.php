<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile; // Make sure this model exists

class AuthentikMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $username = $request->header('x-authentik-username');
        $name = $request->header('x-authentik-name');
        $email = $request->header('x-authentik-email');

        $logoutUrl = env('APP_URL') . env('AUTHENTIK_LOGOUT_URL');

        if (!$username || !$email) {
            return redirect($logoutUrl)->withErrors(['authentik' => 'Missing Authentik-Username or Email header.']);
        }

        $user = User::firstOrCreate(
            ['username' => $username],
            ['email' => $email]
        );

        if (!$user->profile) {
            Profile::create([
                'user_id' => $user->id,
                'name' => $name ?: $username,
            ]);
        }

        $request->attributes->set('user', $user);

        return $next($request);
    }
}

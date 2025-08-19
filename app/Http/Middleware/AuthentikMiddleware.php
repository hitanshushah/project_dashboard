<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;

class AuthentikMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);
        
        if (empty($subdomain)) {
            return $this->handleNormalAuthentication($request, $next);
        }
        
        if ($subdomain === 'admin') {
            return $this->handleNormalAuthentication($request, $next);
        }
        
        $profile = Profile::where('public_url', $subdomain)->first();
        
        if ($profile && $profile->share_profile) {
            $request->attributes->set('public_profile', $profile);
            $request->attributes->set('public_user_id', $profile->user_id);
            
            return $next($request);
        } else {
            $logoutUrl = env('APP_URL') . env('AUTHENTIK_LOGOUT_URL');
            return redirect($logoutUrl)->withErrors(['authentik' => 'Invalid subdomain or profile not shared.']);
        }
    }

    private function handleNormalAuthentication(Request $request, Closure $next)
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

    /**
     * Extract subdomain from host
     */
    private function extractSubdomain(string $host): string
    {
        $parts = explode('.', $host);
        
        if (count($parts) > 2) {
            return $parts[0];
        }
        return '';
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthTokenMiddleware
{
    /**
     * Authenticate the request via a Bearer token from the personal_access_tokens table.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::where('token', hash('sha256', $token))->first();

        if (! $accessToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken->update(['last_used' => now()]);

        Auth::setUser($accessToken->user);

        $request->merge(['current_access_token' => $accessToken]);

        return $next($request);
    }
}

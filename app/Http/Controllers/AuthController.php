<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user and return a token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $plainToken = Str::random(64);

        $user->personalAccessTokens()->create([
            'token' => hash('sha256', $plainToken),
            'last_used' => now(),
        ]);

        return response()->json([
            'user' => $user,
            'token' => $plainToken,
        ], 201);
    }

    /**
     * Authenticate a user and return a token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($validated)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        $plainToken = Str::random(64);

        $user->personalAccessTokens()->create([
            'token' => hash('sha256', $plainToken),
            'last_used' => now(),
        ]);

        return response()->json([
            'user' => $user,
            'token' => $plainToken,
        ]);
    }

    /**
     * Revoke the current token and log out.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken $currentToken */
        $currentToken = $request->get('current_access_token');
        $currentToken->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}

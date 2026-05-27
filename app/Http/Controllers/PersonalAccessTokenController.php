<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PersonalAccessTokenController extends Controller
{
    /**
     * List all tokens for the authenticated user (token hash hidden).
     */
    public function index(): JsonResponse
    {
        $tokens = Auth::user()
            ->personalAccessTokens()
            ->select(['id', 'user_id', 'last_used', 'created_at', 'updated_at'])
            ->latest()
            ->get();

        return response()->json($tokens);
    }

    /**
     * Revoke a specific token. Owner only.
     */
    public function destroy(PersonalAccessToken $personalAccessToken): JsonResponse
    {
        if ($personalAccessToken->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $personalAccessToken->delete();

        return response()->json(['message' => 'Token revoked successfully.']);
    }
}

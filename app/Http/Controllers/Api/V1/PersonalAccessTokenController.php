<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreatePersonalAccessTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalAccessTokenController extends Controller
{
    /**
     * List user's personal access tokens.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
            ]);

        return response()->json(['data' => $tokens]);
    }

    /**
     * Create a new personal access token.
     */
    public function store(CreatePersonalAccessTokenRequest $request): JsonResponse
    {
        $token = $request->user()->createToken(
            $request->validated('name'),
            $request->validated('abilities', ['*']),
            $request->validated('expires_at') ? now()->parse($request->validated('expires_at')) : null,
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'name' => $token->accessToken->name,
            'abilities' => $token->accessToken->abilities,
        ], 201);
    }

    /**
     * Revoke a personal access token.
     */
    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->first();

        if (! $token) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        $token->delete();

        return response()->json(['message' => 'Token revoked.']);
    }
}

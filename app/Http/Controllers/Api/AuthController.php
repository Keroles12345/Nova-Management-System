<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request, JwtTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($data);

        return $this->tokenResponse($user, $tokens, 201);
    }

    public function login(Request $request, JwtTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $user = User::where('email', $data['email'])->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 401);
        }

        return $this->tokenResponse($user, $tokens);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->only(['id', 'name', 'email', 'role'])]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->increment('token_version');

        return response()->json(['message' => 'Signed out and revoked existing tokens.']);
    }

    private function tokenResponse(User $user, JwtTokenService $tokens, int $status = 200): JsonResponse
    {
        $issued = $tokens->issue($user);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $issued['token'],
            'expires_in' => $issued['expires_in'],
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ], $status);
    }
}

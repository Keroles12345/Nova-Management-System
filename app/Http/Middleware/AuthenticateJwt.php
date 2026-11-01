<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtTokenService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(private readonly JwtTokenService $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization', '');

        if (! preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
            throw new AuthenticationException('A valid bearer token is required.');
        }

        $claims = $this->tokens->verify($matches[1]);
        $user = $claims === null ? null : User::find($claims['sub']);

        if ($user === null || $user->token_version !== $claims['ver']) {
            throw new AuthenticationException('The bearer token is invalid or expired.');
        }

        auth()->setUser($user);

        return $next($request);
    }
}

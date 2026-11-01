<?php

namespace App\Services;

use App\Models\User;
use JsonException;

class JwtTokenService
{
    /**
     * @return array{token: string, expires_in: int}
     */
    public function issue(User $user): array
    {
        $now = now()->timestamp;
        $ttl = (int) config('jwt.ttl');
        $header = $this->encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = $this->encode([
            'iss' => config('jwt.issuer'),
            'sub' => $user->id,
            'ver' => $user->token_version,
            'iat' => $now,
            'exp' => $now + $ttl,
        ]);
        $message = $header.'.'.$payload;
        $signature = hash_hmac('sha256', $message, (string) config('jwt.secret'), true);

        return [
            'token' => $message.'.'.$this->base64UrlEncode($signature),
            'expires_in' => $ttl,
        ];
    }

    /**
     * @return array{sub: int, ver: int}|null
     */
    public function verify(string $token): ?array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;
        $headerJson = $this->base64UrlDecode($encodedHeader);
        $payloadJson = $this->base64UrlDecode($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature);

        if ($headerJson === null || $payloadJson === null || $signature === null) {
            return null;
        }

        try {
            $header = json_decode($headerJson, true, flags: JSON_THROW_ON_ERROR);
            $claims = json_decode($payloadJson, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        $message = $encodedHeader.'.'.$encodedPayload;
        $expectedSignature = hash_hmac('sha256', $message, (string) config('jwt.secret'), true);
        $subject = $claims['sub'] ?? null;
        $issuedAt = $claims['iat'] ?? null;
        $expiresAt = $claims['exp'] ?? null;
        $version = $claims['ver'] ?? null;

        if (! is_array($header)
            || ($header['alg'] ?? null) !== 'HS256'
            || ! is_array($claims)
            || ($claims['iss'] ?? null) !== config('jwt.issuer')
            || (! is_int($subject) && (! is_string($subject) || ! ctype_digit($subject)))
            || ! is_int($issuedAt)
            || ! is_int($expiresAt)
            || ! is_int($version)
            || $issuedAt > now()->timestamp + 60
            || $expiresAt <= now()->timestamp
            || $expiresAt <= $issuedAt
            || ! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        return ['sub' => (int) $subject, 'ver' => $version];
    }

    private function encode(array $value): string
    {
        return $this->base64UrlEncode(json_encode($value, JSON_THROW_ON_ERROR));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        if ($value === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
            return null;
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4),
            true,
        );

        return $decoded === false ? null : $decoded;
    }
}

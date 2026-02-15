<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MagicLinkLoginService
{
    protected int $expiryMinutes = 15;

    public function issue(User $user): string
    {
        $token = Str::random(64);

        Cache::put(
            $this->tokenCacheKey($token),
            (string) $user->getKey(),
            now()->addMinutes($this->expiryMinutes),
        );

        return URL::temporarySignedRoute(
            'auth.magic-link.login',
            now()->addMinutes($this->expiryMinutes),
            [
                'user' => $user->getKey(),
                'token' => $token,
            ],
        );
    }

    public function consume(User $user, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $expectedUserId = Cache::pull($this->tokenCacheKey($token));

        if (! is_string($expectedUserId)) {
            return false;
        }

        return hash_equals($expectedUserId, (string) $user->getKey());
    }

    private function tokenCacheKey(string $token): string
    {
        return "magic-login-token:{$token}";
    }
}

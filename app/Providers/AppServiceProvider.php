<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('fonnte-webhook', function (Request $request): array {
            $normalizedSender = User::normalizePhoneNumber((string) $request->input('sender'));
            $senderKey = $normalizedSender !== null
                ? 'fonnte-sender:'.$normalizedSender
                : 'fonnte-ip:'.$request->ip();

            $perSender = max(10, (int) config('services.fonnte.rate_limit_per_minute', 40));
            $global = max(100, (int) config('services.fonnte.global_rate_limit_per_minute', 1200));

            return [
                Limit::perMinute($perSender)->by($senderKey),
                Limit::perMinute($global)->by('fonnte-global'),
            ];
        });
    }
}

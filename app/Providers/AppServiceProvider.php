<?php

namespace App\Providers;

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
        \App\Models\Report::observe(\App\Observers\ReportObserver::class);

        RateLimiter::for('telegram-webhook', function (Request $request): array {
            $chatId = (string) data_get($request->all(), 'message.chat.id', '');
            $senderKey = $chatId !== ''
                ? 'telegram-chat:'.$chatId
                : 'telegram-ip:'.$request->ip();

            $perSender = max(10, (int) config('services.telegram.rate_limit_per_minute', 40));
            $global = max(100, (int) config('services.telegram.global_rate_limit_per_minute', 1200));

            return [
                Limit::perMinute($perSender)->by($senderKey),
                Limit::perMinute($global)->by('telegram-global'),
            ];
        });
    }
}

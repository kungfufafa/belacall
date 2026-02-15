<?php

namespace App\Models;

use App\Enums\ReportPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SlaConfig extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(function (self $config): void {
            if (! $config->isDirty('priority')) {
                return;
            }

            $oldPriority = (string) $config->getRawOriginal('priority');

            if ($oldPriority !== '') {
                Cache::forget(self::cacheKey($oldPriority));
            }
        });

        static::saved(function (self $config): void {
            $priority = $config->priority instanceof ReportPriority
                ? $config->priority->value
                : (string) $config->priority;

            if ($priority !== '') {
                Cache::forget(self::cacheKey($priority));
            }
        });

        static::deleted(function (self $config): void {
            $priority = $config->priority instanceof ReportPriority
                ? $config->priority->value
                : (string) $config->priority;

            if ($priority !== '') {
                Cache::forget(self::cacheKey($priority));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'priority' => ReportPriority::class,
        ];
    }

    public static function forPriority(ReportPriority $priority): self
    {
        $cacheKey = self::cacheKey($priority->value);
        $cacheTtl = 3600; // 1 hour

        return Cache::remember($cacheKey, $cacheTtl, function () use ($priority) {
            return static::query()->where('priority', $priority->value)->first()
                ?? new static([
                    'priority' => $priority,
                    'response_time_minutes' => self::defaultResponseTime($priority),
                    'resolution_time_minutes' => self::defaultResolutionTime($priority),
                ]);
        });
    }

    /**
     * Compute response and resolution deadlines from a given start time.
     *
     * @return array{response_deadline: Carbon, resolution_deadline: Carbon}
     */
    public function computeDeadlines(Carbon $from): array
    {
        return [
            'response_deadline' => $from->copy()->addMinutes($this->response_time_minutes),
            'resolution_deadline' => $from->copy()->addMinutes($this->resolution_time_minutes),
        ];
    }

    private static function defaultResponseTime(ReportPriority $priority): int
    {
        return match ($priority) {
            ReportPriority::URGENT => 15,
            ReportPriority::HIGH => 60,
            ReportPriority::MEDIUM => 240,
            ReportPriority::LOW => 1440,
        };
    }

    private static function defaultResolutionTime(ReportPriority $priority): int
    {
        return match ($priority) {
            ReportPriority::URGENT => 120,
            ReportPriority::HIGH => 480,
            ReportPriority::MEDIUM => 2880,
            ReportPriority::LOW => 10080,
        };
    }

    private static function cacheKey(string $priority): string
    {
        return "sla_config_{$priority}";
    }
}

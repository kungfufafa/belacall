<?php

namespace App\Models;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function generateTicketNumber(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $ticketNumber = 'T-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));

            // Use database-level unique constraint to handle race conditions
            // If a race condition occurs, the database will reject the duplicate
            // and we'll generate a new one
            try {
                // Check if exists first to avoid unnecessary constraint violations
                if (! self::query()->where('ticket_number', $ticketNumber)->exists()) {
                    return $ticketNumber;
                }
            } catch (QueryException $e) {
                // If unique constraint violation occurs during the check,
                // just continue to the next iteration
                if (str_contains($e->getMessage(), 'Duplicate entry')
                    || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                    $attempts++;
                    continue;
                }
                throw $e;
            }

            $attempts++;
        }

        // Fallback: if we still have collisions, use a longer random string with timestamp
        return 'T-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(12));
    }

    protected function casts(): array
    {
        return [
            'priority' => ReportPriority::class,
            'status' => ReportStatus::class,
            'response_deadline' => 'datetime',
            'resolution_deadline' => 'datetime',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ReportEvidence::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ReportHistory::class);
    }
}

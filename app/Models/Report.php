<?php

namespace App\Models;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'T-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (self::query()->where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }

    protected function casts(): array
    {
        return [
            'priority' => ReportPriority::class,
            'status' => ReportStatus::class,
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

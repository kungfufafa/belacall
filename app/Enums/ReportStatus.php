<?php

namespace App\Enums;

enum ReportStatus: string
{
    case SUBMITTED = 'SUBMITTED';
    case VERIFIED = 'VERIFIED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';
    case REJECTED = 'REJECTED';
    case NEEDS_REVISION = 'NEEDS_REVISION';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Masuk',
            self::VERIFIED => 'Terverifikasi',
            self::IN_PROGRESS => 'Diproses',
            self::RESOLVED => 'Selesai',
            self::CLOSED => 'Ditutup',
            self::REJECTED => 'Ditolak',
            self::NEEDS_REVISION => 'Perlu Revisi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUBMITTED => 'info',
            self::VERIFIED => 'primary',
            self::IN_PROGRESS => 'secondary',
            self::RESOLVED => 'success',
            self::CLOSED => 'gray',
            self::REJECTED => 'danger',
            self::NEEDS_REVISION => 'warning',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::CLOSED, self::REJECTED], true);
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * @return array<int, self>
     */
    private function allowedTransitions(): array
    {
        return match ($this) {
            self::SUBMITTED => [self::VERIFIED, self::NEEDS_REVISION, self::REJECTED],
            self::VERIFIED => [self::IN_PROGRESS, self::NEEDS_REVISION, self::REJECTED],
            self::IN_PROGRESS => [self::RESOLVED, self::NEEDS_REVISION],
            self::RESOLVED => [self::CLOSED, self::IN_PROGRESS],
            self::NEEDS_REVISION => [self::SUBMITTED, self::REJECTED],
            self::CLOSED, self::REJECTED => [],
        };
    }
}

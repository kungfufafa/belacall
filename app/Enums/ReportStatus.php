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
}

<?php

namespace App\Enums;

enum ReportPriority: string
{
    case URGENT = 'Urgent';
    case HIGH = 'High';
    case MEDIUM = 'Medium';
    case LOW = 'Low';

    public function label(): string
    {
        return match ($this) {
            self::URGENT => 'Mendesak',
            self::HIGH => 'Tinggi',
            self::MEDIUM => 'Sedang',
            self::LOW => 'Rendah',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::URGENT => 'danger',
            self::HIGH => 'warning',
            self::MEDIUM => 'info',
            self::LOW => 'gray',
        };
    }
}

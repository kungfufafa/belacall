<?php

namespace App\Enums;

enum ReportCategory: string
{
    case GENERAL = 'General';
    case INFRASTRUKTUR = 'Infrastruktur';
    case SAMPAH = 'Sampah';
    case KEAMANAN = 'Keamanan';
    case PELAYANAN = 'Pelayanan';
    case LAINNYA = 'Lainnya';
}

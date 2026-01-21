<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case WARGA = 'warga';
    case OPERATOR = 'operator';
    case PIMPINAN = 'pimpinan';
}

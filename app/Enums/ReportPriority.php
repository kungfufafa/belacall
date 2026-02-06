<?php

namespace App\Enums;

enum ReportPriority: string
{
    case URGENT = 'Urgent';
    case HIGH = 'High';
    case MEDIUM = 'Medium';
    case LOW = 'Low';
}

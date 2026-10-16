<?php

namespace App\Enums;

enum IncidentPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}

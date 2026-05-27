<?php

namespace App\Enums;

enum PollStatus: string
{
    // Add your cases here
    case OPEN = 'open';
    case CLOSED = 'closed';
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

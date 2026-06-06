<?php

namespace App\Enums;

enum LinkDirection: string
{
    case Upstream = 'upstream';
    case Downstream = 'downstream';
    case Bidirectional = 'bidirectional';

    public function label(): string
    {
        return match ($this) {
            self::Upstream => 'Upstream',
            self::Downstream => 'Downstream',
            self::Bidirectional => 'Bidirectional',
        };
    }
}

<?php

namespace App\Enums;

enum OdcSplitterRouteMode: string
{
    case Direct = 'direct';
    case ViaJoint = 'via_joint';

    public function label(): string
    {
        return match ($this) {
            self::Direct => 'ODC → ODP',
            self::ViaJoint => 'ODC → Joint → ODP',
        };
    }
}

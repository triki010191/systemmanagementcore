<?php

namespace App\Enums;

enum OdcSplitterTargetType: string
{
    case Odp = 'odp';
    case Odc = 'odc';

    public function label(): string
    {
        return match ($this) {
            self::Odp => 'ODP',
            self::Odc => 'ODC',
        };
    }

    public function jointType(): CoreJointType
    {
        return match ($this) {
            self::Odp => CoreJointType::OdcToOdp,
            self::Odc => CoreJointType::OdcToOdc,
        };
    }
}

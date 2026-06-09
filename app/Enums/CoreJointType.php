<?php

namespace App\Enums;

enum CoreJointType: string
{
    case OtbToOdc = 'otb_odc';
    case OdcToOdp = 'odc_odp';
    case OdcToOdc = 'odc_odc';

    public function label(): string
    {
        return match ($this) {
            self::OtbToOdc => 'OTB → ODC',
            self::OdcToOdp => 'ODC → ODP',
            self::OdcToOdc => 'ODC → ODC',
        };
    }

    public function sourceType(): NetworkNodeType
    {
        return match ($this) {
            self::OtbToOdc => NetworkNodeType::Otb,
            self::OdcToOdp, self::OdcToOdc => NetworkNodeType::Odc,
        };
    }

    public function targetType(): NetworkNodeType
    {
        return match ($this) {
            self::OtbToOdc => NetworkNodeType::Odc,
            self::OdcToOdp => NetworkNodeType::Odp,
            self::OdcToOdc => NetworkNodeType::Odc,
        };
    }
}

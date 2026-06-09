<?php

namespace App\Enums;

enum OdcSplitterRatio: string
{
    case Ratio1x4 = '1:4';
    case Ratio1x8 = '1:8';

    public function label(): string
    {
        return $this->value;
    }

    public function outputCount(): int
    {
        return match ($this) {
            self::Ratio1x4 => 4,
            self::Ratio1x8 => 8,
        };
    }
}

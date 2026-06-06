<?php

namespace App\Enums;

enum CoreStatus: string
{
    case Available = 'available';
    case Used = 'used';
    case Reserved = 'reserved';
    case Broken = 'broken';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Used => 'Terpakai',
            self::Reserved => 'Direservasi',
            self::Broken => 'Rusak',
            self::Maintenance => 'Maintenance',
        };
    }
}

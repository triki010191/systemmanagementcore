<?php

namespace App\Enums;

enum PortStatus: string
{
    case Empty = 'empty';
    case Active = 'active';
    case Reserved = 'reserved';
    case Broken = 'broken';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Empty => 'Kosong',
            self::Active => 'Aktif',
            self::Reserved => 'Direservasi',
            self::Broken => 'Rusak',
            self::Maintenance => 'Maintenance',
        };
    }
}

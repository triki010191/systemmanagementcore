<?php

namespace App\Enums;

use App\Models\Customer;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Otb;
use App\Models\Pop;
use Illuminate\Database\Eloquent\Model;

enum NetworkNodeType: string
{
    case Pop = 'pop';
    case Olt = 'olt';
    case Otb = 'otb';
    case Odc = 'odc';
    case Odp = 'odp';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Pop => 'POP / Selter',
            self::Olt => 'OLT',
            self::Otb => 'OTB',
            self::Odc => 'ODC',
            self::Odp => 'ODP',
            self::Customer => 'Pelanggan',
        };
    }

    /** Hierarchy order for path tracing (low = upstream). */
    public function hierarchyLevel(): int
    {
        return match ($this) {
            self::Pop => 0,
            self::Olt => 1,
            self::Otb => 2,
            self::Odc => 3,
            self::Odp => 4,
            self::Customer => 5,
        };
    }

    public function routeSlug(): string
    {
        return match ($this) {
            self::Pop => 'pops',
            self::Olt => 'olts',
            self::Otb => 'otbs',
            self::Odc => 'odcs',
            self::Odp => 'odps',
            self::Customer => 'customers',
        };
    }

    public static function fromRouteSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->routeSlug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    public function expectedParentType(): ?self
    {
        return match ($this) {
            self::Pop => null,
            self::Olt => self::Pop,
            self::Otb => self::Olt,
            self::Odc => self::Otb,
            self::Odp => self::Odc,
            self::Customer => self::Odp,
        };
    }

    /** @return class-string<Model> */
    public function domainModelClass(): string
    {
        return match ($this) {
            self::Pop => Pop::class,
            self::Olt => Olt::class,
            self::Otb => Otb::class,
            self::Odc => Odc::class,
            self::Odp => Odp::class,
            self::Customer => Customer::class,
        };
    }

    public function requiresGps(): bool
    {
        return match ($this) {
            self::Pop, self::Otb, self::Odc, self::Odp, self::Customer => true,
            default => false,
        };
    }

    public function usesAutoCode(): bool
    {
        return match ($this) {
            self::Otb, self::Odc, self::Odp, self::Customer => true,
            default => false,
        };
    }

    public function requiresQr(): bool
    {
        return match ($this) {
            self::Pop, self::Otb, self::Odc, self::Odp, self::Customer => true,
            default => false,
        };
    }
}

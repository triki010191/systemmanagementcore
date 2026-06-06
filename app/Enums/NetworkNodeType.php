<?php

namespace App\Enums;

enum NetworkNodeType: string
{
    case Pop = 'pop';
    case Olt = 'olt';
    case Otb = 'otb';
    case Odc = 'odc';
    case Splitter = 'splitter';
    case Odp = 'odp';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Pop => 'POP / Selter',
            self::Olt => 'OLT',
            self::Otb => 'OTB',
            self::Odc => 'ODC',
            self::Splitter => 'Splitter',
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
            self::Splitter => 4,
            self::Odp => 5,
            self::Customer => 6,
        };
    }

    public function routeSlug(): string
    {
        return match ($this) {
            self::Pop => 'pops',
            self::Olt => 'olts',
            self::Otb => 'otbs',
            self::Odc => 'odcs',
            self::Splitter => 'splitters',
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
            self::Splitter => self::Odc,
            self::Odp => self::Splitter,
            self::Customer => self::Odp,
        };
    }

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    public function domainModelClass(): string
    {
        return match ($this) {
            self::Pop => \App\Models\Pop::class,
            self::Olt => \App\Models\Olt::class,
            self::Otb => \App\Models\Otb::class,
            self::Odc => \App\Models\Odc::class,
            self::Splitter => \App\Models\Splitter::class,
            self::Odp => \App\Models\Odp::class,
            self::Customer => \App\Models\Customer::class,
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
            self::Pop, self::Otb, self::Odc, self::Odp, self::Splitter, self::Customer => true,
            default => false,
        };
    }
}

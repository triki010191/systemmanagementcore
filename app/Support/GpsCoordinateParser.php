<?php

namespace App\Support;

class GpsCoordinateParser
{
    /** @return array{lat: float, lng: float}|null */
    public static function parse(?string $input): ?array
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $input = trim(str_replace(["\xc2\xa0", '，'], [',', ','], $input));

        if (! str_contains($input, ',') && preg_match('/^(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)$/', $input, $matches)) {
            return self::validate((float) $matches[1], (float) $matches[2]);
        }

        $normalized = str_replace([';', '|'], ',', $input);
        $parts = array_values(array_filter(array_map('trim', explode(',', $normalized)), fn ($part) => $part !== ''));

        if (count($parts) === 2) {
            return self::validate((float) $parts[0], (float) $parts[1]);
        }

        if (count($parts) === 4 && self::looksLikeCommaDecimalPair($parts)) {
            return self::validate(
                (float) ($parts[0].'.'.$parts[1]),
                (float) ($parts[2].'.'.$parts[3]),
            );
        }

        return null;
    }

    public static function format(?float $latitude, ?float $longitude): string
    {
        if ($latitude === null || $longitude === null) {
            return '';
        }

        return rtrim(rtrim(sprintf('%.8f', $latitude), '0'), '.')
            .', '
            .rtrim(rtrim(sprintf('%.8f', $longitude), '0'), '.');
    }

    /** @param list<string> $parts */
    private static function looksLikeCommaDecimalPair(array $parts): bool
    {
        foreach ($parts as $part) {
            if (! preg_match('/^-?\d+$/', $part)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{lat: float, lng: float}|null */
    private static function validate(float $lat, float $lng): ?array
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }
}

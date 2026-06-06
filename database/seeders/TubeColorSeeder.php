<?php

namespace Database\Seeders;

use App\Models\TubeColor;
use Illuminate\Database\Seeder;

class TubeColorSeeder extends Seeder
{
    /** EIA/TIA-598-A standard 12-tube color sequence (Bahasa Indonesia). */
    private const COLORS = [
        1 => ['Biru', '#0000FF'],
        2 => ['Orange', '#FF8C00'],
        3 => ['Hijau', '#00AA00'],
        4 => ['Cokelat', '#8B4513'],
        5 => ['Abu-abu', '#708090'],
        6 => ['Putih', '#FFFFFF'],
        7 => ['Merah', '#FF0000'],
        8 => ['Hitam', '#000000'],
        9 => ['Kuning', '#FFD700'],
        10 => ['Ungu', '#8B00FF'],
        11 => ['Mawar', '#FF69B4'],
        12 => ['Aqua', '#00FFFF'],
    ];

    public function run(): void
    {
        foreach (self::COLORS as $tubeNumber => [$name, $hex]) {
            TubeColor::query()->updateOrCreate(
                ['standard' => 'eia_tia_598a', 'tube_number' => $tubeNumber],
                [
                    'color_name' => $name,
                    'hex_code' => $hex,
                    'tracer_pattern' => 'solid',
                    'is_active' => true,
                ],
            );
        }
    }
}

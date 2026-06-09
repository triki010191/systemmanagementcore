<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Demo seeder disabled — network hierarchy no longer includes splitter.
 *             Enter real network data via Aset Jaringan menu.
 */
class NetworkDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('NetworkDemoSeeder is disabled. Use real data entry via Network Assets.');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSampleSeeder extends Seeder
{
    /** @var list<array{name: string, email: string, role: string}> */
    private array $samples = [
        ['name' => 'Super Admin', 'email' => 'admin@hinet.local', 'role' => 'super-admin'],
        ['name' => 'NOC Operator', 'email' => 'noc@hinet.local', 'role' => 'noc'],
        ['name' => 'Teknisi Lapangan', 'email' => 'teknisi@hinet.local', 'role' => 'teknisi'],
        ['name' => 'Customer Service', 'email' => 'cs@hinet.local', 'role' => 'customer-service'],
        ['name' => 'Manager Jaringan', 'email' => 'manager@hinet.local', 'role' => 'manager'],
    ];

    public function run(): void
    {
        foreach ($this->samples as $sample) {
            $user = User::query()->updateOrCreate(
                ['email' => $sample['email']],
                [
                    'name' => $sample['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$sample['role']]);
        }
    }
}

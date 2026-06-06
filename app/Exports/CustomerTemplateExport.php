<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CustomerTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'code',
            'name',
            'phone',
            'email',
            'address',
            'latitude',
            'longitude',
            'odp_code',
            'odp_port',
            'service_type',
            'onu_serial',
            'drop_length_m',
        ];
    }

    public function array(): array
    {
        return [
            ['', 'Siti Aminah', '081298765432', 'siti@email.com', 'Jl. Thamrin No.5', '-6.1782', '106.8682', 'ODP-001', '4', 'home', 'ONU-ZTE-001', '65'],
        ];
    }

    public function title(): string
    {
        return 'Customer Import';
    }
}

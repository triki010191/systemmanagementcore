<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class OdpTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'code',
            'name',
            'latitude',
            'longitude',
            'address',
            'parent_odc_code',
            'port_capacity',
            'cores_from_odc',
        ];
    }

    public function array(): array
    {
        return [
            ['ODP-002', 'ODP Jalan Thamrin', '-6.1780', '106.8680', 'Jl. Thamrin, Jakarta', 'ODC-001', '16', '2'],
        ];
    }

    public function title(): string
    {
        return 'ODP Import';
    }
}

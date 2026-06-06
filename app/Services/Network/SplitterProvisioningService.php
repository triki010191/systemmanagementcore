<?php

namespace App\Services\Network;

use App\Enums\PortStatus;
use App\Enums\SplitterPortDirection;
use App\Enums\SplitterRatio;
use App\Models\Splitter;

class SplitterProvisioningService
{
    public function provision(Splitter $splitter): void
    {
        if ($splitter->ports()->exists()) {
            return;
        }

        $outputCount = $splitter->ratio instanceof SplitterRatio
            ? $splitter->ratio->outputPortCount()
            : (int) ($splitter->output_port_count ?: 8);

        $splitter->ports()->create([
            'port_number' => 1,
            'direction' => SplitterPortDirection::Input,
            'status' => PortStatus::Empty,
            'label' => 'IN-1',
        ]);

        for ($i = 1; $i <= $outputCount; $i++) {
            $splitter->ports()->create([
                'port_number' => $i,
                'direction' => SplitterPortDirection::Output,
                'status' => PortStatus::Empty,
                'label' => "OUT-{$i}",
            ]);
        }

        $splitter->update([
            'input_port_count' => 1,
            'output_port_count' => $outputCount,
        ]);
    }
}

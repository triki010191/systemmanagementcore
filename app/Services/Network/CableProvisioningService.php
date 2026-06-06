<?php

namespace App\Services\Network;

use App\Enums\CoreStatus;
use App\Models\FiberCable;
use App\Models\TubeColor;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CableProvisioningService
{
    private const VALID_CORE_COUNTS = [12, 24, 48, 96];

    public function __construct(
        private readonly QrCodeService $qrCodeService,
    ) {}

    public function provision(FiberCable $cable): void
    {
        if (! in_array($cable->core_count, self::VALID_CORE_COUNTS, true)) {
            throw new InvalidArgumentException(
                "Core count must be one of: ".implode(', ', self::VALID_CORE_COUNTS)
            );
        }

        $tubeCount = (int) ($cable->tube_count ?: $cable->core_count / 12);

        if ($cable->tubes()->exists() || $cable->cores()->exists()) {
            $this->qrCodeService->generateForCable($cable);

            return;
        }

        $colors = TubeColor::query()
            ->where('standard', 'eia_tia_598a')
            ->orderBy('tube_number')
            ->get()
            ->keyBy('tube_number');

        DB::transaction(function () use ($cable, $tubeCount, $colors) {
            $coreNumber = 1;

            for ($tubeNumber = 1; $tubeNumber <= $tubeCount; $tubeNumber++) {
                $color = $colors->get($tubeNumber) ?? $colors->first();

                $tube = $cable->tubes()->create([
                    'tube_number' => $tubeNumber,
                    'tube_color_id' => $color->id,
                    'core_count' => 12,
                ]);

                for ($position = 1; $position <= 12; $position++) {
                    $cable->cores()->create([
                        'cable_tube_id' => $tube->id,
                        'core_number' => $coreNumber,
                        'tube_number' => $tubeNumber,
                        'core_position_in_tube' => $position,
                        'tube_color_id' => $color->id,
                        'color_name' => $color->color_name,
                        'status' => CoreStatus::Available,
                    ]);

                    $coreNumber++;
                }
            }
        });

        $this->qrCodeService->generateForCable($cable->fresh());
    }
}

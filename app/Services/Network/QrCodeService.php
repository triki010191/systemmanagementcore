<?php

namespace App\Services\Network;

use App\Models\FiberCable;
use App\Models\NetworkNode;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /** @var list<string> */
    private const NODE_DISK_PATH = 'qr/nodes';

    private const CABLE_DISK_PATH = 'qr/cables';

    public function scanUrl(NetworkNode $node): string
    {
        return route('scan.show', $node->uuid);
    }

    public function scanUrlForCable(FiberCable $cable): string
    {
        return route('scan.cable', $cable->code);
    }

    public function generateForNode(NetworkNode $node): string
    {
        if (! $node->type->requiresQr()) {
            throw new RuntimeException(__('hfnms.qr_not_supported_for_type'));
        }

        $relativePath = self::NODE_DISK_PATH.'/'.$node->uuid.'.svg';
        $this->writePng($this->scanUrl($node), $relativePath);

        $node->update(['qr_code_path' => $relativePath]);

        return Storage::disk('public')->url($relativePath);
    }

    public function generateForCable(FiberCable $cable): string
    {
        $relativePath = self::CABLE_DISK_PATH.'/'.$cable->code.'.svg';
        $this->writePng($this->scanUrlForCable($cable), $relativePath);

        $cable->update(['qr_code_path' => $relativePath]);

        return Storage::disk('public')->url($relativePath);
    }

    public function publicUrl(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        return Storage::disk('public')->exists($relativePath)
            ? Storage::disk('public')->url($relativePath)
            : null;
    }

    private function writePng(string $content, string $relativePath): void
    {
        Storage::disk('public')->makeDirectory(dirname($relativePath));

        $image = QrCode::format('svg')
            ->size(320)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($content);

        Storage::disk('public')->put($relativePath, $image);
    }
}

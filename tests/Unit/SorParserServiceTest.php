<?php

namespace Tests\Unit;

use App\Services\Network\SorParserService;
use App\Support\Sor\BellcoreSorReader;
use Tests\TestCase;

class SorParserServiceTest extends TestCase
{
    public function test_parses_ascii_sor_metrics(): void
    {
        $path = sys_get_temp_dir().'/test-ascii-'.uniqid().'.sor';
        file_put_contents($path, implode("\n", [
            'Total Loss: 0.42 dB',
            'Fiber Length: 2.500 km',
            'Event @ 1.100 km Loss 0.42 dB',
        ]));

        $result = app(SorParserService::class)->parse($path);

        unlink($path);

        $this->assertTrue($result->success);
        $this->assertSame(0.42, $result->totalLossDb);
        $this->assertSame(2.5, $result->distanceKm);
        $this->assertSame(1.1, $result->faultDistanceKm);
        $this->assertCount(1, $result->eventPoints);
    }

    public function test_parses_bellcore_binary_sor(): void
    {
        $content = $this->buildMinimalBellcoreSor();
        $path = sys_get_temp_dir().'/test-bellcore-'.uniqid().'.sor';
        file_put_contents($path, $content);

        $result = app(SorParserService::class)->parse($path);

        unlink($path);

        $this->assertTrue($result->success);
        $this->assertSame(0.35, $result->totalLossDb);
        $this->assertNotNull($result->distanceKm);
        $this->assertNotEmpty($result->eventPoints);
    }

    public function test_bellcore_reader_returns_null_for_plain_text(): void
    {
        $reader = new BellcoreSorReader;

        $this->assertNull($reader->parse("Total Loss: 0.35 dB\n"));
    }

    private function buildMinimalBellcoreSor(): string
    {
        $indexRaw = 146800;
        $groupIndex = $indexRaw * 1e-5;
        $factor = 1e-4 * 0.299792458 / $groupIndex;
        $distanceKm = 1.2;
        $timeRaw = (int) round($distanceKm / $factor);
        $lossEndRaw = (int) round($distanceKm / $factor);

        $fxdParams = 'FxdParams'."\0";
        $fxdParams .= pack('V', time());
        $fxdParams .= 'km';
        $fxdParams .= pack('v', 1550);
        $fxdParams .= pack('l', 0).pack('l', 0);
        $fxdParams .= pack('v', 1).pack('v', 100);
        $fxdParams .= pack('V', 1000).pack('V', 1000);
        $fxdParams .= pack('V', $indexRaw);
        $fxdParams .= str_repeat("\0", 50);

        $keyEvents = 'KeyEvents'."\0";
        $keyEvents .= pack('v', 1);
        $keyEvents .= pack('v', 1);
        $keyEvents .= pack('V', $timeRaw);
        $keyEvents .= pack('v', 0).pack('v', 350);
        $keyEvents .= pack('l', 0);
        $keyEvents .= str_pad('09999LS', 8, "\0");
        $keyEvents .= pack('V', 0).pack('V', 0).pack('V', 0).pack('V', 0).pack('V', 0);
        $keyEvents .= "\0";
        $keyEvents .= pack('l', 350);
        $keyEvents .= pack('l', 0);
        $keyEvents .= pack('V', $lossEndRaw);
        $keyEvents .= pack('v', 0).pack('l', 0).pack('V', 0);

        $genParams = 'GenParams'."\0".str_repeat("\0", 20);
        $supParams = 'SupParams'."\0".str_repeat("\0", 20);

        $blocks = [
            'GenParams' => $genParams,
            'SupParams' => $supParams,
            'FxdParams' => $fxdParams,
            'KeyEvents' => $keyEvents,
        ];

        $mapBody = '';
        $blockData = '';

        foreach ($blocks as $name => $data) {
            $mapBody .= $name."\0";
            $mapBody .= pack('v', 200);
            $mapBody .= pack('V', strlen($data));
            $blockData .= $data;
        }

        $mapHeader = 'Map'."\0";
        $mapHeader .= pack('v', 200);
        $mapSize = strlen($mapHeader) + 4 + 2 + strlen($mapBody);

        $map = $mapHeader;
        $map .= pack('V', $mapSize);
        $map .= pack('v', count($blocks) + 1);
        $map .= $mapBody;

        return $map.$blockData;
    }
}

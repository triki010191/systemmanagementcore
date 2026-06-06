<?php

namespace App\Enums;

enum SplitterRatio: string
{
    case Ratio1x2 = '1:2';
    case Ratio1x4 = '1:4';
    case Ratio1x8 = '1:8';
    case Ratio1x16 = '1:16';
    case Ratio1x32 = '1:32';
    case Ratio1x64 = '1:64';

    public function outputPortCount(): int
    {
        return (int) explode(':', $this->value)[1];
    }
}

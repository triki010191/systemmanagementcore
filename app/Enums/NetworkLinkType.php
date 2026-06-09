<?php

namespace App\Enums;

enum NetworkLinkType: string
{
    case FiberCable = 'fiber_cable';
    case PatchCord = 'patch_cord';
    case Splice = 'splice';
    case Drop = 'drop';
}

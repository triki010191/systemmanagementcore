<?php

namespace App\Enums;

enum NetworkNodeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';
    case Fault = 'fault';
}

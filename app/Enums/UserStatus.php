<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'Active';
    case Suspended = 'Suspended';
}

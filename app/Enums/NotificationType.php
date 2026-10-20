<?php

namespace App\Enums;

enum NotificationType: string
{
    case Inquiry = 'inquiry';
    case Payment = 'payment';
    case Account = 'account';
    case System = 'system';
}

<?php

namespace App\Enums;

enum InstalmentStatus: string
{
    case Paid = 'Paid';
    case Due = 'Due';
    case Upcoming = 'Upcoming';
    case Overdue = 'Overdue';
}

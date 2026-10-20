<?php

namespace App\Enums;

enum ScheduleStatus: string
{
    case Active = 'Active Schedule';
    case Completed = 'Completed';
    case Overdue = 'Overdue';

    public function pillClass(): string
    {
        return match ($this) {
            self::Completed => 'pill pill--positive',
            self::Overdue => 'pill pill--danger',
            self::Active => 'pill pill--info',
        };
    }
}

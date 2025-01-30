<?php 

// src/Enum/TimeSlot.php
namespace App\Enum;

enum TimeSlot: string
{
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';

    public function getStartTime(): string
    {
        return match($this) {
            self::MORNING => '08:00',
            self::AFTERNOON => '13:00'
        };
    }

    public function getEndTime(): string
    {
        return match($this) {
            self::MORNING => '12:00',
            self::AFTERNOON => '17:00'
        };
    }
}
<?php

namespace App\Support;

class TimeDisplay
{
    public static function minutesToHours(int|float|string|null $minutes): float
    {
        return round(((float) $minutes) / 60, 4);
    }

    public static function hoursToMinutes(int|float|string|null $hours): int
    {
        return (int) round(((float) $hours) * 60);
    }

    public static function formatHours(int|float|string|null $hours): string
    {
        return self::formatMinutes(self::hoursToMinutes($hours));
    }

    public static function formatMinutes(int|float|string|null $minutes): string
    {
        $minutes = (int) round((float) $minutes);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.'hr'.($hours === 1 ? '' : 's');
        }

        if ($remainingMinutes > 0 || $parts === []) {
            $parts[] = $remainingMinutes.'min'.($remainingMinutes === 1 ? '' : 's');
        }

        return implode(' ', $parts);
    }
}

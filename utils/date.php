<?php

namespace TodoListGold\Utils\Date;

use DateInterval;
use DateTime;
use DateTimeZone;

class DateUtils
{
    public const KEY_DATE = 'date';
    public const KEY_TIMEZONE = 'timezone';


    public static function getActualYear(): int
    {
        return (int) date('Y');
    }

    public static function getActualMonth(): int
    {
        return (int) date('m');
    }

    public static function getActualMonthLeadingZero(): string
    {
        return date('m');
    }

    public static function getActualDay(): int
    {
        return (int) date('d');
    }

    public static function getLastDayOfMonth(int $month, int $year): int
    {
        return (int) date('t', mktime(0, 0, 0, $month, 1, $year));
    }

    public static function getDaysDifference(DateTime $date): int
    {
        $now = new DateTime();

        $hasDatePassed = $now > $date;
        $daysUntil = $now->diff($date)->days;

        return $hasDatePassed ? -$daysUntil : $daysUntil;
    }

    public static function checkIfInYear(DateTime $fecha): bool
    {
        $currentYear = self::getActualYear();
        $otherYear = $fecha->format(DateFormat::YEAR);
        
        return $currentYear == $otherYear;
    }

    public static function constructDateFromJson(array $json): DateTime
    {
        $dateStr = $json[self::KEY_DATE];
        $timezoneStr = $json[self::KEY_TIMEZONE] ?? 'Europe/Berlin';

        $timezone = new DateTimeZone($timezoneStr);
        return new DateTime($dateStr, $timezone);
    }

    public static function secondsToInterval(int $seconds): DateInterval
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return new DateInterval("PT{$hours}H{$minutes}M{$secs}S");
    }
}


class DateFormat
{
    public const ISO_8601 = 'Y-m-d';
    public const ES = 'd/m/Y';
    public const ES_COMPRESSED = 'dmy';
    public const ES_PLUS_WEEKDAY = 'l d/m/Y';
    public const YMD_2_YEARS_UNDERSCORES = 'y_m_d';
    public const YMD_2_YEARS_SLASHES = 'y/m/d';
    public const YEAR = 'Y';
    public const MONTH = 'm';
    public const DAY = 'd';
}


class TimeFormat
{
    public const ISO_8601 = 'H:i:s';
}


class DateTimeFormat
{
    public const ATOM = 'Y-m-d\TH:i:sP';
    public const ISO_8601 = 'Y-m-d H:i:s';
    public const ISO_8601_T = 'Y-m-d\TH:i:s';
    public const FILENAME_SAFE = 'Y-m-d_H-i-s';
    public const TIMESTAMP = 'Y-m-d H:i:s.v';
    public const ES = 'd/m/Y H:i:s';
    public const ES_NO_SECONDS = 'd/m/Y H:i';
    public const US = 'm/d/Y H:i:s';
}

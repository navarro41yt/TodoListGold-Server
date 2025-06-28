<?php

namespace TodoListGold\Utils;

class Utils
{
    public const VERSION_NAME = 'TodoListGold® v1.0';
    public const RAND_NAME_LENGTH = 8;

    public const UPPERCASE_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const LOWERCASE_CHARACTERS = 'abcdefghijklmnopqrstuvwxyz';
    public const NUMBERS = '0123456789';
    public const SPECIAL_CHARACTERS = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    public const ALL_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';

    public static function generateRandomName(int $length = self::RAND_NAME_LENGTH): string
    {
        $characters = self::UPPERCASE_CHARACTERS . self::LOWERCASE_CHARACTERS;
        $arrayLen = strlen($characters) - 1;

        $name = '';
        for ($i = 0; $i < $length; $i++) {
            $randPos = rand(0, $arrayLen);
            $name .= $characters[$randPos];
        }

        return $name;
    }
    
    public static function randomFloat(): float
    {
        $randomFloat = (float) (mt_rand() / mt_getrandmax());
        return $randomFloat;
    }

    public static function randomInt(int $min, int $max): int
    {
        return rand($min, $max);
    }
}


class WebUtils
{
    public static function wrapInScript(string $code): string
    {
        return "<script>$code</script>";
    }

    public static function alert(string $text): void
    {
        echo "<script>alert('$text');</script>";
    }

    public static function consoleInfo(string $text): string
    {
        return "<script>console.info('$text');</script>";
    }

    public static function consoleLog(string $text): string
    {
        return "<script>console.log('$text');</script>";
    }
}


class MathUtils
{
    public static function formatPercentage(float $percentage, ?int $decimals = 2): string
    {
        $result = $percentage * 100;
        $result = $decimals !== null ? round($result, $decimals) : $result;
        return number_format($result, $decimals);
    }

    public static function percentage(float $x, float $t, ?int $maxDecimals = 2): float
    {
        $result = ($x / $t) * 100;
        return $maxDecimals !== null ? round($result, $maxDecimals) : $result;
    }

    public static function proportionalPercentage(float $x, float $y, ?int $maxDecimals = 2): float
    {
        $result = $x / ($x + $y) * 100;
        return $maxDecimals !== null ? round($result, $maxDecimals) : $result;
    }

    public static function toNE(float $lat, float $lon): string
    {
        $latHem = $lat >= 0 ? 'N' : 'S';
        $lonHem = $lon >= 0 ? 'E' : 'W';

        $lat = abs($lat);
        $lon = abs($lon);

        $latDeg = floor($lat);
        $lonDeg = floor($lon);

        $latMin = floor(($lat - $latDeg) * 60);
        $lonMin = floor(($lon - $lonDeg) * 60);

        $latSec = round(($lat - $latDeg - $latMin / 60) * 3600, 1);
        $lonSec = round(($lon - $lonDeg - $lonMin / 60) * 3600, 1);

        return sprintf('%02d°%02d\'%04.1f"%s %03d°%02d\'%04.1f"%s', $latDeg, $latMin, $latSec, $latHem, $lonDeg, $lonMin, $lonSec, $lonHem);
    }

    /** @return array{float, float}|null */
    public static function toDecimalCoords(string $lat, string $lon): ?array
    {
        $pattern = '/(\d+)°\s*(\d+)\'\s*(\d+(?:\.\d+)?)\"\s*([NSEWO])/i';

        if (!preg_match($pattern, $lat, $latMatches)) {
            return null;
        }

        if (!preg_match($pattern, $lon, $lonMatches)) {
            return null;
        }

        $convert = function ($matches): float {
            [$full, $degrees, $minutes, $seconds, $direction] = $matches;
            $decimal = $degrees + $minutes / 60 + $seconds / 3600;
            if (in_array(strtoupper($direction), ['S', 'W', 'O'])) {
                $decimal *= -1;
            }
            return $decimal;
        };

        return [$convert($latMatches), $convert($lonMatches)];
    }

    public static function haversineFx(array $coords1, array $coords2): float
    {
        [$lat1, $lon1] = $coords1;
        [$lat2, $lon2] = $coords2;

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $R = 6371;

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = pow(sin($dLat / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($dLon / 2), 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }

    /**
     * Compare two float values safely for sorting.
     *
     * @param float $epsilon Optional precision tolerance
     * @return int Returns -1 if $a < $b, 0 if equal, 1 if $a > $b
     */
    public static function compareFloat(float $a, float $b, float $epsilon = 0.00001): int
    {
        if (abs($a - $b) < $epsilon) {
            return 0;
        }

        return ($a < $b) ? -1 : 1;
    }
}


trait CaseInsensitiveEnum
{
    public static function fromCI(string $v): self
    {
        $v = strtoupper($v);
        return self::from($v);
    }

    public static function tryFromCI(string $v): ?self
    {
        $v = strtoupper($v);
        return self::tryFrom($v);
    }
}


function remSuffix(string $text, string $suffix): string
{
    return str_ends_with($text, $suffix)
        ? substr($text, 0, -strlen($suffix)) 
        : $text
    ;
}


function remPrefix(string $text, string $prefix): string
{
    return str_starts_with($text, $prefix)
        ? substr($text, strlen($prefix)) 
        : $text
    ;
}


function remPreSuffix(string $stack, string $needle): string
{
    return remSuffix(remPrefix($stack, $needle), $needle);
}

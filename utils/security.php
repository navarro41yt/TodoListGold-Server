<?php

namespace TodoListGold\Security;

use TodoListGold\Utils\Dev\ServerUtils;
use TodoListGold\Utils\Utils;

class SecurityUtils
{
    public const PASSWORD_MIN_LENGTH = 8;
    public const SALT_LENGTH = 32;

    public const UPPERCASE_REGEX = '/[A-Z]/';
    public const LOWERCASE_REGEX = '/[a-z]/';
    public const NUMBER_REGEX = '/\d/';
    public const SPECIAL_CHAR_REGEX = '/[\W_]/';

    public const PLANIFICACION_SPECIAL_OPS_PASS = '?Pl4n1f1cacion2025!';

    public static function isPasswordValid(string $password): bool
    {
        $mayusculaChk = (bool) preg_match(self::UPPERCASE_REGEX, $password);
        $lowercaseChk = (bool) preg_match(self::LOWERCASE_REGEX, $password);
        $numeroChk = (bool) preg_match(self::NUMBER_REGEX, $password);
        $specialCharChk = (bool) preg_match(self::SPECIAL_CHAR_REGEX, $password);
        $lengthChk = strlen($password) >= self::PASSWORD_MIN_LENGTH;

        return $mayusculaChk && $lowercaseChk && $numeroChk && $specialCharChk && $lengthChk;
    }

    public static function generateSalt(): string
    {
        $characters = Utils::ALL_CHARACTERS;
        $arrayLen = strlen($characters) - 1;

        $salt = '';
        for ($i = 0; $i < self::SALT_LENGTH; $i++) {
            $randPos = rand(0, $arrayLen);
            $salt .= $characters[$randPos];
        }

        return $salt;
    }

    public static function hash(string ...$strings): string
    {
        $hash = '';
        foreach ($strings as $string) {
            $hash .= $string;
        }

        return md5($hash);
    }
}


class EnvManager
{
    public const ENV_PATH = ROOT_DIR . '.env.json';
    public const KEY_MAIL_CLIENT_DIR = 'MAIL_CLIENT_DIR';
    public const KEY_DB_PARAMS = 'DB_PARAMS';
    public const KEY_DB_BIGDATA_PARAMS = 'DB_BIGDATA_PARAMS';
    public const KEY_PRODUCTION = 'PRODUCTION';
    public const KEY_BASE_URL = 'BASE_URL';
    public const KEY_CRITICAL_OPERATIONS_PASS = 'CRITICAL_OPERATIONS_PASS';
    public const MAINTENANCE = 'MAINTENANCE';
    public const MAINTENANCE_CAUSE = 'MAINTENANCE_CAUSE';
    public const MAINTENANCE_START = 'MAINTENANCE_START';
    public const MAINTENANCE_END = 'MAINTENANCE_END';
    public const USER_DEFAULT_PASS = 'USER_DEFAULT_PASS';

    private static array $env;
    private static bool $production = false;

    public static function _init(): void
    {
        self::$env = json_decode(file_get_contents(self::ENV_PATH), true);
        self::$production = self::getBool(self::KEY_PRODUCTION);
        date_default_timezone_set('Europe/Berlin');
    }

    public static function isProduction(): bool
    {
        return self::$production;
    }

    public static function get(string $key): mixed
    {
        return self::$env[$key] ?? null;
    }

    public static function getStr(string $key): ?string
    {
        return (string) self::$env[$key] ?? null;
    }

    public static function getInt(string $key): ?int
    {
        return (int) self::$env[$key] ?? null;
    }

    public static function getFloat(string $key): ?float
    {
        return (float) self::$env[$key] ?? null;
    }

    public static function getBool(string $key): ?bool
    {
        return (bool) self::$env[$key] ?? null;
    }

    public static function getArray(string $key): ?array
    {
        return (array) self::$env[$key] ?? null;
    }

    public static function getStrFromPath(string $key): string
    {
        $path = self::$env[$key] ?? null;
        $exists = ServerUtils::pathExists($path);

        if ($path === null || !$exists) {
            ServerUtils::死ね(1, "FATAL ERROR ENV NOT CONFIGURED PROPERLY");
        }

        return trim(file_get_contents($path));
    }

    public static function set(string $key, mixed $value): void
    {
        self::$env[$key] = $value;
        file_put_contents(self::ENV_PATH, json_encode(self::$env));
        self::$production = self::getBool(self::KEY_PRODUCTION);
    }
}

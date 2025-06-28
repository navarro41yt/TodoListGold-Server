<?php

namespace TodoListGold\DB;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use TodoListGold\IO\Path;
use TodoListGold\Security\EnvManager;
use TodoListGold\Utils\Dev\ServerUtils;

use const TodoListGold\Constants\S_ROLE;


abstract class EntityManagerProvider
{
    public const PROXY_DIR = ROOT_DIR . 'cache' . DS . 'proxies';

    public const KEY_PASSWORD = 'password';

    protected static ?EntityManager $em = null;

    abstract public static function getDbKey(): string;

    public static function getEntityManager(): EntityManager
    {
        if (static::$em === null) {
            if (!is_dir(static::PROXY_DIR)) {
                ServerUtils::createPathIfNotExists(static::PROXY_DIR);
            }

            $metadataPath = Path::joinRoot('model');
            $config = ORMSetup::createAttributeMetadataConfiguration(
                paths: [$metadataPath],
                isDevMode: true
            );
            $config->setProxyDir(static::PROXY_DIR);

            $connOpts = EnvManager::getArray(static::getDbKey());
            $connOpts[static::KEY_PASSWORD] = file_get_contents($connOpts[static::KEY_PASSWORD]);

            $conn = DriverManager::getConnection($connOpts);
            static::$em = new EntityManager($conn, $config);
        }

        return static::$em;
    }
}


class CoreEntityManagerProvider extends EntityManagerProvider
{
    protected static ?EntityManager $em = null;

    public static function getDbKey(): string
    {
        return EnvManager::KEY_DB_PARAMS;
    }
}


class BigDataEntityManagerProvider extends EntityManagerProvider
{
    protected static ?EntityManager $em = null;

    public static function getDbKey(): string
    {
        return EnvManager::KEY_DB_BIGDATA_PARAMS;
    }
}


class DoctrineVar
{
    public const STRING = 'string';
    public const INTEGER = 'integer';
    public const BOOLEAN = 'boolean';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const TIME = 'time';
    public const TEXT = 'text';
    public const FLOAT = 'float';
    public const DECIMAL = 'decimal';
    public const BIGINT = 'bigint';
    public const SMALLINT = 'smallint';
    public const BLOB = 'blob';
    public const GUID = 'guid';
    public const JSON = 'json';
    public const ARRAY = 'array';
    public const OBJECT = 'object';
    public const PERSIST = 'persist';
    public const REMOVE = 'remove';
    public const EAGER = 'EAGER';
    public const CASCADE = 'CASCADE';
    public const PERSIST_REMOVE = [self::PERSIST, self::REMOVE];
}


enum Role: int
{
    case ADMINISTRADOR = 1;

    public function getName(): string
    {
        return match ($this) {
            Role::ADMINISTRADOR => "Administrador"
        };
    }

    public function is(...$roles): bool
    {
        if ($this === Role::ADMINISTRADOR) {
            return true;
        }

        foreach ($roles as $r) {
            if ($this == $r) {
                return true;
            }
        }
        return false;
    }

    /** @param ?Role ...$roles */
    public static function checkRole(...$roles): bool
    {
        $hasPermission = false;
        $sessionRole = $_SESSION[S_ROLE];
        $role = Role::tryFrom($sessionRole);

        if ($role !== null) {
            if ($role == Role::ADMINISTRADOR) {
                return true;
            }
            foreach ($roles as $r) {
                if ($role == $r) {
                    $hasPermission = true;
                    break;
                }
            }
        }

        return $hasPermission;
    }

    /** @param ?Role ...$roles */
    public static function checkRoleAndRedirect(...$roles): void
    {
        if (!Role::checkRole(...$roles)) {
            ServerUtils::redirect('inicio', '', 1);
        }
    }
}


enum ActiveMode: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;
    case ALL = 3;

    public function getBool(): ?bool
    {
        return match ($this) {
            ActiveMode::ACTIVE => true,
            ActiveMode::INACTIVE => false,
            ActiveMode::ALL => null,
        };
    }
}

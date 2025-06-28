<?php

namespace TodoListGold\Model;

use DateTime;
use Exception;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use TodoListGold\DB\ActiveMode;
use TodoListGold\DB\DoctrineVar;
use TodoListGold\DB\CoreEntityManagerProvider;
use TodoListGold\Logging\DBLogger;
use TodoListGold\Logging\Level;
use TodoListGold\Logging\Logger;
use TodoListGold\Utils\Date\DateTimeFormat;
use TodoListGold\Exceptions\NotImplementedException;

use const TodoListGold\Constants\UNDEFINED;

/**
 * @template T
 */
class PaginatedList
{
    /** @var T[] */
    public array $list;
    public int $totalItems;
    public int $resultsPerPage;
    public int $page = 1;

    /**
     * @param T[] $list
     */
    public function __construct(array $list, int $totalItems, int $resultsPerPage, int $page)
    {
        $this->list = $list;
        $this->totalItems = $totalItems;
        $this->resultsPerPage = $resultsPerPage;
        $this->page = $page;
    }

    public function getTotalPages(): int
    {
        return ceil($this->totalItems / $this->resultsPerPage);
    }
}


interface IWebEntity
{
    public static function constructFromPost(): static;
}


interface IJsonEntity extends JsonSerializable
{
    public static function constructFromJson(array $json): static;
}


trait JsonEntityTrait
{
    public static function constructMultipleFromJson(array $json): array
    {
        $entList = [];
        foreach ($json as $item) {
            $entList[] = static::constructFromJson($item);
        }

        return $entList;
    }
}


interface IAlternateJsonSerialize
{
    public function alternateJsonSerialize(): array;
}


/** @param IAlternateJsonSerialize|IAlternateJsonSerialize[] $entity */
function jsonEncode(IAlternateJsonSerialize|array $entity, int $flags = 0, int $depth = 512): string
{
    $json = jsonPreEncode($entity);
    return json_encode($json, $flags, $depth);
}


/** @param IAlternateJsonSerialize|IAlternateJsonSerialize[] $entity */
function jsonPreEncode(IAlternateJsonSerialize|array $entity): array
{
    if (is_array($entity)) {
        $json = [];
        foreach ($entity as $item) {
            $json[] = $item->alternateJsonSerialize();
        }
    } else {
        $json = $entity->alternateJsonSerialize();
    }

    return $json;
}


abstract class DTO implements IJsonEntity
{
    public function copyFrom(object $other): void
    {
        $reflector = new ReflectionClass($this);
        $selfVars = $reflector->getProperties();

        $otherReflector = new ReflectionClass($other);
        $otherVars = $otherReflector->getProperties();

        foreach ($selfVars as $selfVar) {
            $selfVar->setAccessible(true);
            $selfName = $selfVar->getName();

            foreach ($otherVars as $otherVar) {
                $otherVar->setAccessible(true);
                $otherName = $otherVar->getName();

                if ($selfName === $otherName) {
                    $otherValue = $otherVar->getValue($other);
                    $selfVar->setValue($this, $otherValue);
                    break;
                }
            }
        }
    }
}


#region Entities
#[ORM\MappedSuperclass]
abstract class Entity implements JsonSerializable, Stringable
{
    public static function getEntityName(): string
    {
        $className = static::class;
        $className = substr($className, strrpos($className, '\\') + 1);
        return substr($className, 0, -6);
    }

    public function merge(object $other, bool $overwriteZeroes = true): void
    {
        $reflector = new ReflectionClass($this);

        foreach ($reflector->getProperties() as $property) {
            $property->setAccessible(true);

            $otherValue = $property->getValue($other);

            if ($overwriteZeroes) {
                if ($otherValue === 0 || $otherValue === false || !empty($otherValue)) {
                    $property->setValue($this, $otherValue);
                }
            } else {
                if (!empty($otherValue) || $otherValue === false) {
                    $property->setValue($this, $otherValue);
                }
            }
        }
    }

    public function mergeSkipCollections(object $other): void
    {
        $reflector = new ReflectionClass($this);

        foreach ($reflector->getProperties() as $property) {
            $property->setAccessible(true);

            $otherValue = $property->getValue($other);

            if ($otherValue instanceof Collection) {
                continue;
            }

            if ($otherValue === 0 || $otherValue === false || !empty($otherValue)) {
                $property->setValue($this, $otherValue);
            }
        }
    }

    public function formatDateJson(?DateTime $dateTime): string
    {
        return $dateTime?->format(DateTimeFormat::ATOM) ?? '';
    }

    public function equals(object $other): bool
    {
        throw new NotImplementedException('equals() not implemented');
    }

    public function jsonSerialize(): array
    {
        throw new NotImplementedException('jsonSerialize() not implemented');
    }

    public function __tostring(): string
    {
        return json_encode($this);
    }
}


#[ORM\MappedSuperclass]
abstract class IdentifiedEntity extends Entity
{
    use IdentifiedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: self::COL_ID, type: DoctrineVar::INTEGER)]
    public ?int $id = 0;
}


/**
 * @template T
 */
interface IRelatedRepository
{
    /**
     * @param T $ent
     */
    public function getRelatedEntities($ent): void;
}


interface ICustomDeactivation
{
    public function deactivateById(mixed $id): mixed;
}


#region Repository
/** @template T */
abstract class Repository
{
    public const OP_CORRECT = 0;
    public const OP_ERROR = 1;

    protected EntityManager $em;

    public const KEY_ASCENDING = 'ASC';
    public const KEY_DESCENDING = 'DESC';

    /**
     * @var EntityRepository<T>
     */
    protected EntityRepository $repo;
    public int $maxResults;

    /**
     * @param class-string<T> $entityClass
     * @param class-string $trait
     */
    protected function entityHasTrait(string $trait): bool
    {
        $entityClass = $this->repo->getClassName();
        $reflect = new ReflectionClass($entityClass);
        $traits = $reflect->getTraitNames();

        return in_array($trait, $traits);
    }

    public function getRef(string $entityClass, int $id): object
    {
        return $this->em->getReference($entityClass, $id);
    }

    protected function defaultCriteria(ActiveMode $activo): array
    {
        $searchCriteria = [];
        if ($activo !== ActiveMode::ALL && $this->entityHasTrait(ActivoTrait::class)) {
            $searchCriteria[U_FLD_ACTIVO] = $activo === ActiveMode::ACTIVE;
        }
        return $searchCriteria;
    }

    public function defaultOrder(): array
    {
        return [IdentifiedEntity::FLD_ID => self::KEY_ASCENDING];
    }

    protected function isTransactionStarted(): bool
    {
        return $this->em->getConnection()->isTransactionActive();
    }

    public function beginTransaction(): void
    {
        $this->em->beginTransaction();
    }

    public function commit(): void
    {
        $this->em->commit();
    }

    public function rollback(): void
    {
        if ($this->em->isOpen() && $this->isTransactionStarted()) {
            $this->em->rollback();
        }
    }

    public function autoCommit(bool $cond): bool
    {
        if ($cond) {
            $this->commit();
        } else {
            $this->rollback();
        }

        return $cond;
    }

    protected function getOffset(int $pag): int|null
    {
        return ($pag >= 0) ? ($pag - 1) * $this->maxResults : null;
    }

    /** @return T[] */
    public function getAll(ActiveMode $activeMode = ActiveMode::ALL): array
    {
        $criteria = $this->defaultCriteria($activeMode);
        return $this->repo->findBy($criteria);
    }

    /** @return T[] */
    public function getAllAlphabetically(ActiveMode $activeMode = ActiveMode::ALL): array
    {
        if (!$this->entityHasTrait(NombreTrait::class)) {
            throw new Exception('Entity "' . $this->repo->getClassName() . '" does not have NamedTrait');
        }

        $searchCriteria = $this->defaultCriteria($activeMode);
        $orderCriteria = [U_FLD_NOMBRE => self::KEY_ASCENDING];
        return $this->repo->findBy($searchCriteria, $orderCriteria);
    }

    public function getPaginated(ActiveMode $activo = ActiveMode::ACTIVE, int $pag = 0): PaginatedList
    {
        $searchCriteria = $this->defaultCriteria($activo);

        $offset = $pag === 0 ? null : $this->getOffset($pag);

        $list = $this->repo->findBy($searchCriteria, null, $this->maxResults, $offset);
        $totalItems = $this->repo->count($searchCriteria);

        $paginatedList = new PaginatedList($list, $totalItems, $this->maxResults, $pag);
        return $paginatedList;
    }

    /**
     * @param T[] $results
     */
    public function paginate(array $results, int $pag): PaginatedList
    {
        $totalItems = count($results);
        $offset = $this->getOffset($pag);

        $list = array_slice($results, $offset, $this->maxResults);

        $paginatedList = new PaginatedList($list, $totalItems, $this->maxResults, $pag);
        return $paginatedList;
    }

    /** @return ?T */
    public function getByPK(mixed $pk)
    {
        return $this->repo->find($pk);
    }

    /** @return ?T */
    public function getByNombre(string $nombre, ActiveMode $activo = ActiveMode::ALL): ?object
    {
        if (!$this->entityHasTrait(NombreTrait::class)) {
            throw new Exception('Entity "' . $this->repo->getClassName() . '" does not have NamedTrait');
        }

        $searchCriteria = $this->defaultCriteria($activo);
        $searchCriteria[U_FLD_NOMBRE] = $nombre;

        return $this->repo->findOneBy($searchCriteria);
    }

    public function count(ActiveMode $activo = ActiveMode::ACTIVE): int
    {
        $searchCriteria = $this->defaultCriteria($activo);
        return $this->repo->count($searchCriteria);
    }

    public function countQuery(Query $query, ActiveMode $activo, ...$params): int
    {
        $countQuery = clone $query;
        $countQuery->setFirstResult(0);
        $countQuery->setMaxResults(null);

        for ($i = 0; $i < count($params); $i += 2) {
            $key = $params[$i];
            $value = $params[$i + 1];

            if ($value === null || $value === 0 || $value === '') {
                continue;
            }

            $countQuery->setParameter($key, $value);
        }

        if ($activo !== ActiveMode::ALL) {
            $countQuery->setParameter('activo', $activo->getBool());
        }

        return (int) $countQuery->getScalarResult();
    }

    /**
     * @param T $ent
     */
    private function persist($ent): void
    {
        $this->em->persist($ent);
    }

    private function flush(): void
    {
        $this->em->flush();
    }

    /**
     * @param T $ent
     */
    protected function _save($ent): void
    {

        try {
            $this->persist($ent);
            $this->flush();
            $this->logIntermediate($ent->id ?? 0, 'guardado', Level::INFO);
        } catch (Exception $e) {
            DBLogger::logDB($e);
            $this->logIntermediate($ent->id ?? 0, 'fallado al guardar', Level::ERROR);
            $this->rollback();
            throw $e;
        }
    }

    /**
     * @param T[] $ents
     */
    protected function _saveAll(array $ents): void
    {
        foreach ($ents as $ent) {
            $this->persist($ent);
        }

        $this->flush();
    }

    /**
     * @param T $ent
     */
    public function save($ent, bool $manualControl = false): bool
    {
        if (!$manualControl) {
            $this->beginTransaction();
        }

        try {
            $this->_save($ent);
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }

        if (!$manualControl) {
            $this->commit();
        }
        return true;
    }

    /**
     * @param T[] $ents
     */
    public function saveAll(array $ents): bool
    {
        $this->beginTransaction();

        try {
            $this->_saveAll($ents);
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * @param T $ent
     */
    protected function _delete($ent): void
    {
        $this->em->remove($ent);
        $this->em->flush();
    }

    /**
     * @param T $ent
     */
    public function delete($ent): bool
    {
        $this->beginTransaction();

        try {
            $this->_delete($ent);
            $this->logIntermediate($ent->id, 'eliminado', Level::INFO);
        } catch (Exception $e) {
            DBLogger::logDB($e);
            $this->logIntermediate($ent->id, 'fallado al eliminar', Level::ERROR);
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * @param T[] $ents
     */
    public function deleteAllEnts(array $ents): bool
    {
        $this->beginTransaction();

        try {
            foreach ($ents as $ent) {
                $this->_delete($ent);
            }
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    public function deleteById(int $id): bool
    {
        $ent = $this->getByPK($id);

        if ($ent === null) {
            return false;
        }

        return $this->delete($ent);
    }

    /**
     * @param int[] $ids
     */
    public function deleteAllById(array $ids): bool
    {
        $this->beginTransaction();

        try {
            foreach ($ids as $id) {
                $ent = $this->getByPK($id);
                if ($ent !== null) {
                    $this->_delete($ent);
                }
            }
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    # NOTE (Antonio): ⚠️💀⚠️ This method is dangerous AF, caution needed to use it ⚠️💀⚠️
    public function truncate(): bool
    {
        $connection = $this->em->getConnection();
        $tableName = $this->em->getClassMetadata($this->repo->getClassName())->getTableName();

        $connection->beginTransaction();

        try {
            $platform = $connection->getDatabasePlatform();

            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            $connection->executeStatement($platform->getTruncateTableSQL($tableName, true));
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

            $this->logIntermediate('all', 'Tabla eliminada', Level::INFO);
            $connection->commit();
            return true;
        } catch (Exception $e) {
            DBLogger::logDB($e);
            $this->logIntermediate('all', 'Fallado al eliminar tabla', Level::CRITICAL);
            $connection->rollBack();
            return false;
        }
    }

    public function deactivateOrReactivate(mixed $id, bool $manualControl = false): bool
    {
        if (!$this->entityHasTrait(ActivoTrait::class)) {
            throw new Exception('Entity "' . $this->repo->getClassName() . '" does not have ActivoTrait');
        }

        $ent = $this->getByPK($id);
        $action = $ent->activo ? 'desactivado' : 'activado';

        if ($ent === null) {
            $this->logIntermediate($id, "| No Se encuentra ($action)", Level::ERROR);
            return false;
        }

        $ent->activo = !$ent->activo;

        $this->logIntermediate($id, $action, Level::INFO);
        return $this->save($ent, $manualControl);
    }

    public function logIntermediate(mixed $id, string $action, Level $level): void
    {
        $entType = $this->repo->getClassName();
        $msg = "Se ha $action la Entidad de tipo $entType con ID $id";
        Logger::log($msg, $level);
    }
}


/**
 * @template T
 * @extends Repository<T>
 */
#region AppRepository
abstract class AppRepository extends Repository
{
    /**
     * @param class-string<T> $entityClass
     */
    public function __construct(string $entityClass, int $maxResults)
    {
        $em = CoreEntityManagerProvider::getEntityManager();

        $this->em = $em;
        $this->repo = $em->getRepository($entityClass);
        $this->maxResults = $maxResults;
    }
}


/**
 * @template T
 * @extends IRelatedRepository<T>
 */
interface IExtendedRepository extends IRelatedRepository
{
    /** @param T $ent */
    public function insert($ent): bool|int|Entity|null;

    /** @param T $ent */
    public function update($ent): bool|int|Entity|null;
}


/**
 * @template T
 * @extends AppRepository<T>
 * @implements IExtendedRepository<T>
 */
abstract class ExtendedRepository extends AppRepository implements IExtendedRepository
{
    public function __construct(string $entityClass, int $maxResults)
    {
        parent::__construct($entityClass, $maxResults);
    }
}


/**
 * @template T
 * @extends AppRepository<T>
 * @implements IExtendedRepository<T>
 */
abstract class GenericExtendedRepository extends AppRepository implements IExtendedRepository
{
    public function getRelatedEntities($ent): void
    {
    }

    /**
     * @param T $ent
     */
    public function insert($ent): bool
    {
        $this->getRelatedEntities($ent);

        return $this->save($ent);
    }

    public function update($ent): bool
    {
        $oldEnt = $this->getByPK($ent->id);
        $oldEnt->merge($ent);
        $this->getRelatedEntities($oldEnt);

        return $this->save($oldEnt);
    }
}


#region Static Repository
interface IStaticRepository
{
    public static function getAssociatedEntityClassname(): string;
}


/**
 * @template T
 * @extends AppRepository<T>
 * @implements IStaticRepository
 */
abstract class StaticRepository extends AppRepository implements IStaticRepository
{
    private static ?self $instance = null;

    /** @return ?T */
    public static function sGetByPk(mixed $pk)
    {
        if (self::$instance === null) {
            $className = static::getAssociatedEntityClassname();
            self::$instance = new static($className, 0);
        }

        return self::$instance->getByPK($pk);
    }
}


#region CustomQuery
# TODO (Antonio): Get rid of CustomQuery, leave only ComplexCustomQuery (and rename ComplexCustomQuery to CustomQuery)
class CustomQuery
{
    private QueryBuilder $qb;
    private array $exprs = [];
    private string $alias;

    public function __construct(QueryBuilder $qb, string $alias)
    {
        $this->qb = $qb;
        $this->alias = $alias;
    }

    private function getOffset(int $pag, int $maxResults): int
    {
        return ($pag - 1) * $maxResults;
    }

    public static function builder(): static
    {
        $em = CoreEntityManagerProvider::getEntityManager();
        $qb = $em->createQueryBuilder();

        return new static($qb, 'alias');
    }

    private function formatWhere(string $table, string $op): string
    {
        return "$this->alias.$table $op :$table";
    }

    public function select(): static
    {
        $this->qb->select($this->alias);

        return $this;
    }

    /**
     * @param class-string $table
     */
    public function from(string $table): static
    {
        $this->qb->from($table, $this->alias);

        return $this;
    }

    public function where(string|Orx|Andx $columnX, ?string $op = null, mixed $value = UNDEFINED): static
    {
        if ($value === null || $value === 0 || $value === '') {
            return $this;
        }

        if ($columnX instanceof Orx || $columnX instanceof Andx) {
            $this->qb->where($columnX);
            return $this;
        }

        $this->qb->where($this->formatWhere($columnX, $op));

        return $this;
    }

    public function andWhere(string|Orx|Andx $columnX, ?string $op = null, mixed $value = UNDEFINED): static
    {
        if ($value === null || $value === 0 || $value === '') {
            return $this;
        }

        if ($columnX instanceof Orx || $columnX instanceof Andx) {
            $this->qb->andWhere($columnX);
            return $this;
        }

        $this->qb->andWhere($this->formatWhere($columnX, $op));

        return $this;
    }

    public function orWhere(string|Orx|Andx $columnX, ?string $op = null, mixed $value = UNDEFINED): static
    {
        if ($value === null || $value === 0 || $value === '') {
            return $this;
        }

        if ($columnX instanceof Orx || $columnX instanceof Andx) {
            $this->qb->orWhere($columnX);
            return $this;
        }

        $this->qb->orWhere($this->formatWhere($columnX, $op));

        return $this;
    }

    public function orX(string|Comparison ...$exprs): Orx
    {
        return $this->qb->expr()->orX(...$exprs);
    }

    public function eq(string $column): string
    {
        return $this->qb->expr()->eq("$this->alias.$column", ":$column");
    }

    public function isNull(string $column): string
    {
        return $this->qb->expr()->isNull("$this->alias.$column");
    }

    public function isNotNull(string $column): string
    {
        return $this->qb->expr()->isNotNull("$this->alias.$column");
    }

    public function gt(string $column): Comparison
    {
        $c = $this->qb->expr()->gt("$this->alias.$column", ":$column");

        return $c;
    }

    public function lt(string $column): Comparison
    {
        $c = $this->qb->expr()->lt("$this->alias.$column", ":$column");

        return $c;
    }

    public function eqc(string $column, ?string $parameter = null): Comparison
    {
        if (empty($parameter)) {
            $parameter = $column;
        }

        return $this->qb->expr()->eq("$this->alias.$column", ":$parameter");
    }

    public function neq(string $column, ?string $parameter = null): Comparison
    {
        if (empty($parameter)) {
            $parameter = $column;
        }

        return $this->qb->expr()->neq("$this->alias.$column", ":$parameter");
    }

    public function setParameter(string $name, mixed $value): static
    {
        if ($value === null || $value === 0 || $value === '') {
            return $this;
        }

        $this->qb->setParameter($name, $value);

        return $this;
    }

    public function paginated(int $page, int $limit): static
    {
        $this->offset($page, $limit);
        $this->limit($limit);

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->qb->setMaxResults($limit);

        return $this;
    }

    public function offset(int $page, int $maxResults): static
    {
        $offset = $page === 0 ? null : $this->getOffset($page, $maxResults);
        $this->qb->setFirstResult($offset);

        return $this;
    }

    public function getQuery(): Query
    {
        return $this->qb->getQuery();
    }

    public function orderBy(string $column, string $order): static
    {
        $alias = $this->alias;
        $this->qb->orderBy("$alias.$column", $order);

        return $this;
    }
}


#region ComplexCustomQuery
class ComplexCustomQuery
{
    private QueryBuilder $qb;
    private ?int $limit = null;
    private ?int $offset = null;


    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    public static function builder(): static
    {
        $em = CoreEntityManagerProvider::getEntityManager();

        $qb = $em->createQueryBuilder();
        return new static($qb);
    }

    private function formatWhere(string $column, string $alias, string $op): string
    {
        return "$alias.$column $op :$column";
    }

    public function select($alias): static
    {
        $this->qb->select($alias);

        return $this;
    }

    /**
     * @param class-string $entity
     */
    public function from(string $entity, string $alias): static
    {
        $this->qb->from($entity, $alias);

        return $this;
    }

    /**
     * @param class-string $entity
     */
    public function innerJoin(string $entity, string $aliasL, string $fldL, string $aliasR, string $fldR): static
    {
        $this->qb->innerJoin($entity, $aliasR, 'WITH', "$aliasL.$fldL = $aliasR.$fldR");

        return $this;
    }

    public function where(string|Orx|Andx $columnX, ?string $alias = null, ?string $op = null, mixed $value = UNDEFINED): static
    {
        if ($value === null || $value === 0 || $value === '') {
            return $this;
        }

        if ($columnX instanceof Orx || $columnX instanceof Andx) {
            $this->qb->where($columnX);
            return $this;
        }

        $this->qb->where($this->formatWhere($columnX, $alias, $op));

        return $this;
    }

    public function andWhere(string|Orx|Andx $columnX, ?string $alias = null, ?string $op = null, mixed $value = UNDEFINED): static
    {
        if ($value === null || $value === 0 || $value === '') {
            return $this;
        }

        if ($columnX instanceof Orx || $columnX instanceof Andx) {
            $this->qb->andWhere($columnX);
            return $this;
        }

        $this->qb->andWhere($this->formatWhere($columnX, $alias, $op));

        return $this;
    }

    public function orWhere(string|Orx|Andx $columnX, ?string $alias = null, ?string $op = null, $value = UNDEFINED): static
    {
        if ($value === null || $value === 0 || $value === '') {
            return $this;
        }

        if ($columnX instanceof Orx || $columnX instanceof Andx) {
            $this->qb->orWhere($columnX);
            return $this;
        }

        $this->qb->orWhere($this->formatWhere($columnX, $alias, $op));

        return $this;
    }

    public function andX(string|Comparison ...$exprs): Andx
    {
        return $this->qb->expr()->andX(...$exprs);
    }

    public function orX(string|Comparison ...$exprs): Orx
    {
        return $this->qb->expr()->orX(...$exprs);
    }

    public function neq(string $column, string $alias, ?string $param = null): Comparison
    {
        if ($param === null) {
            $param = $column;
        }

        return $this->qb->expr()->neq("$alias.$column", ":$param");
    }

    public function eq(string $column, string $alias, ?string $param = null): Comparison
    {
        if ($param === null) {
            $param = $column;
        }

        return $this->qb->expr()->eq("$alias.$column", ":$param");
    }

    public function orderBy(string $column, string $alias, string $order): static
    {
        $this->qb->orderBy("$alias.$column", $order);
        return $this;
    }

    public function addOrderBy(string $column, string $alias, string $order): static
    {
        $this->qb->addOrderBy("$alias.$column", $order);
        return $this;
    }

    public function setMaxResults(int $max): static
    {
        $this->limit = $max;
        $this->qb->setMaxResults($max);
        return $this;
    }

    public function setOffset(int $pag, int $maxResults): static
    {
        $this->offset = $this->getOffset($pag, $maxResults);
        $this->qb->setFirstResult($this->offset);
        return $this;
    }

    private function getOffset(int $pag, int $maxResults): int|null
    {
        $offset = ($pag >= 0) ? ($pag - 1) * $maxResults : null;
        $this->offset = $offset;
        return $offset;
    }

    public function setParameter(string $param, mixed $value): static
    {
        if ($value === null || $value === 0 || $value === '') {
            return $this;
        }
 
        $this->qb->setParameter($param, $value);
        return $this;
    }

    public function getPaginatedResult(string $alias, string $idFld): PaginatedList
    {
        $list = $this->qb->getQuery()->getResult();

        $countQb = clone $this->qb;
        $this->semiResetQb($countQb);

        $countQb->select("COUNT({$alias}.{$idFld})");
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $pag = $this->offset !== null && $this->limit !== null
            ? (int) floor($this->offset / $this->limit) + 1
            : 1
        ;

        return new PaginatedList($list, $total, $this->limit, $pag);
    }

    private function semiResetQb(QueryBuilder $qb): void
    {
        $qb->resetDQLPart('orderBy');
        $qb->resetDQLPart('groupBy');
        $qb->resetDQLPart('limit');
        $qb->setMaxResults(null);
        $qb->setFirstResult(null);
    }

    public function getQuery(): Query
    {
        return $this->qb->getQuery();
    }
}

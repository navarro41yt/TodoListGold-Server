<?php

namespace TodoListGold\Model;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use TodoListGold\DB\DoctrineVar;
use TodoListGold\Utils\MathUtils;

#region IdentifiedEntity
trait IdentifiedTrait
{
    public const COL_ID = 'ID';
    public const FLD_ID = 'id';

    public ?int $id = 0;

    /**
     * @param Collection<IdentifiedEntity> | IdentifiedEntity[] $entities
     * @return int[]
     */
    public static function getIds(Collection|array $entities): array
    {
        $entitiesArr = is_array($entities) ? $entities : $entities->toArray();

        return array_map(fn($entity) => $entity->id, $entitiesArr);
    }

    /**
     * @param Collection<IdentifiedTrait> | IdentifiedTrait[] $entities
     * @return IdentifiedTrait[]
     */
    public static function uniques(Collection|array $entities): array
    {
        $entitiesArr = is_array($entities) ? $entities : $entities->toArray();

        $uniqueEntities = [];
        $ids = [];

        foreach ($entitiesArr as $entity) {
            if (!in_array($entity->id, $ids)) {
                $uniqueEntities[] = $entity;
                $ids[] = $entity->id;
            }
        }

        return $uniqueEntities;
    }

    /** @param IdentifiedTrait $other */
    public function equals(object $other): bool
    {
        if ($this->id === 0 && $other->id === 0) {
            return true;
        }

        if ($this->id === 0 || $other->id === 0) {
            return false;
        }

        return $this->id === $other->id;
    }
}


#region StringedIdentifiedEntity
trait StringedIdentifiedTrait
{
    public const COL_ID = 'ID';
    public const FLD_ID = 'id';

    #[ORM\Id]
    #[ORM\Column(name: self::COL_ID, type: DoctrineVar::STRING)]
    public string $id = '';
}


#region ActivoTrait
const U_FLD_ACTIVO = 'activo';

trait ActivoTrait
{
    public const COL_ACTIVO = 'Activo';
    public const FLD_ACTIVO = 'activo';

    #[ORM\Column(name: self::COL_ACTIVO, type: DoctrineVar::BOOLEAN)]
    public bool $activo = true;
}


#region FechaCreacionTrait
trait FechaCreacionTrait
{
    public const COL_FECHA_CREACION = 'FechaCreacion';
    public const FLD_FECHA_CREACION = 'fechaCreacion';

    #[ORM\Column(name: self::COL_FECHA_CREACION, type: DoctrineVar::DATETIME)]
    public ?DateTime $fechaCreacion = null;

    public static function sortCreatedDate(array $arr): array
    {
        usort($arr, fn(FechaCreacionTrait $a, FechaCreacionTrait $b) => $a->fechaCreacion <=> $b->fechaCreacion);
        return $arr;
    }
}


#region FechaModificaciónTrait
trait FechaModificacionTrait
{
    public const COL_FECHA_MODIFICACION = 'FechaModificacion';
    public const FLD_FECHA_MODIFICACION = 'fechaModificacion';

    #[ORM\Column(name: self::COL_FECHA_MODIFICACION, type: DoctrineVar::DATETIME)]
    public ?DateTime $fechaModificacion = null;

    public static function sortModifiedDate(array $arr): array
    {
        usort($arr, fn(FechaModificacionTrait $a, FechaModificacionTrait $b) => $a->fechaModificacion <=> $b->fechaModificacion);
        return $arr;
    }
}


#region FechaEliminacionTrait
trait FechaEliminacionTrait
{
    public const COL_FECHA_ELIMINACION = 'FechaEliminacion';
    public const FLD_FECHA_ELIMINACION = 'fechaEliminacion';

    #[ORM\Column(name: self::COL_FECHA_ELIMINACION, type: DoctrineVar::DATETIME, nullable: true)]
    public ?DateTime $fechaEliminacion = null;

    public static function sortEliminatedDate(array $arr): array
    {
        usort($arr, fn(FechaEliminacionTrait $a, FechaEliminacionTrait $b) => $a->fechaEliminacion <=> $b->fechaEliminacion);
        return $arr;
    }
}


#region FechaRealizacionTrait
trait FechaRealizacionTrait
{
    public const COL_FECHA_REALIZACION = 'FechaRealizacion';
    public const FLD_FECHA_REALIZACION = 'fechaRealizacion';

    #[ORM\Column(name: self::COL_FECHA_REALIZACION, type: DoctrineVar::DATETIME)]
    public ?DateTime $fechaRealizacion = null;
}


#region FechaInicioTrait
const U_FLD_FECHA_INICIO = 'fechaInicio';

trait FechaInicioTrait
{
    public const COL_FECHA_INICIO = 'FechaInicio';
    public const FLD_FECHA_INICIO = U_FLD_FECHA_INICIO;

    #[ORM\Column(name: self::COL_FECHA_INICIO, type: DoctrineVar::DATETIME)]
    public ?DateTime $fechaInicio = null;
}


#region FechaFinTrait
const U_FLD_FECHA_FIN = 'fechaFin';

trait FechaFinTrait
{
    public const COL_FECHA_FIN = 'FechaFin';
    public const FLD_FECHA_FIN = U_FLD_FECHA_FIN;

    #[ORM\Column(name: self::COL_FECHA_FIN, type: DoctrineVar::DATETIME)]
    public ?DateTime $fechaFin = null;
}


#region YearTrait
trait YearTrait
{
    public const COL_ANHO = 'Anho';
    public const FLD_ANHO = 'anho';

    #[ORM\Column(name: self::COL_ANHO, type: DoctrineVar::INTEGER)]
    public int $anho = CURRENT_YEAR;
}


#region NombreTrait
const U_FLD_NOMBRE = 'nombre';

trait NombreTrait
{
    public const COL_NOMBRE = 'Nombre';
    public const FLD_NOMBRE = U_FLD_NOMBRE;

    #[ORM\Column(name: self::COL_NOMBRE, type: DoctrineVar::STRING)]
    public string $nombre = '';

    /**
     * Transforms the name to a "normal" format.
     * tnn stands for "To Normal Name".
     */
    public function tnn(): string
    {
        $name = str_replace('_', ' ', $this->nombre);
        $name = str_replace('nh', 'ñ', $name);
        $name = ucwords($name);
        return $name;
    }

    public function kv(bool $ftt = false): array
    {
        return [$this->id => $ftt ? $this->tnn() : $this->nombre];
    }
}


#region NumeroTrait
trait NumeroTrait
{
    public const COL_NUMERO = 'Numero';
    public const FLD_NUMERO = 'numero';

    #[ORM\Column(name: self::COL_NUMERO, type: DoctrineVar::INTEGER, nullable: true)]
    public ?int $numero = null;
}


#region CodigoTrait
trait CodigoTrait
{
    public const COL_CODIGO = 'Codigo';
    public const FLD_CODIGO = 'codigo';

    #[ORM\Column(name: self::COL_CODIGO, type: DoctrineVar::STRING, nullable: true)]
    public ?string $codigo = null;
}


#region XYZTrait
trait XYZTrait
{
    public const COL_COORD_LAT = 'CoordLat';
    public const COL_COORD_LON = 'CoordLon';
    public const COL_COORD_ALT = 'CoordAlt';

    public const FLD_COORD_LAT = 'coordLat';
    public const FLD_COORD_LON = 'coordLon';
    public const FLD_COORD_ALT = 'coordAlt';

    #[ORM\Column(name: self::COL_COORD_LAT, type: DoctrineVar::DECIMAL)]
    public float $coordLat = 0.0;

    #[ORM\Column(name: self::COL_COORD_LON, type: DoctrineVar::DECIMAL)]
    public float $coordLon = 0.0;

    #[ORM\Column(name: self::COL_COORD_ALT, type: DoctrineVar::DECIMAL)]
    public float $coordAlt = 0.0;

    public function toNE(): string
    {
        return MathUtils::toNE($this->coordLat, $this->coordLon);
    }

    public function checkCoords(): bool
    {
        return ($this->coordLat >= -90 && $this->coordLat <= 90) &&
            ($this->coordLon >= -180 && $this->coordLon <= 180);
    }

    public function isInNullIsland(): bool
    {
        return $this->coordLat === 0.0 && $this->coordLon === 0.0;
    }

    /** @return array{float, float} */
    public function getLatLon(): array
    {
        return [$this->coordLat, $this->coordLon];
    }
}


#region XYZTraitExtended
trait XYZTraitExtended
{
    use XYZTrait;

    public const COL_COORD_LAT_ORIGINAL = 'CoordLatOriginal';
    public const COL_COORD_LON_ORIGINAL = 'CoordLonOriginal';
    public const COL_COORD_ALT_ORIGINAL = 'CoordAltOriginal';

    public const FLD_COORD_LAT_ORIGINAL = 'coordLatOriginal';
    public const FLD_COORD_LON_ORIGINAL = 'coordLonOriginal';
    public const FLD_COORD_ALT_ORIGINAL = 'coordAltOriginal';

    #[ORM\Column(name: self::COL_COORD_LAT_ORIGINAL, type: DoctrineVar::DECIMAL)]
    public float $coordLatOriginal = 0.0;

    #[ORM\Column(name: self::COL_COORD_LON_ORIGINAL, type: DoctrineVar::DECIMAL)]
    public float $coordLonOriginal = 0.0;

    #[ORM\Column(name: self::COL_COORD_ALT_ORIGINAL, type: DoctrineVar::DECIMAL)]
    public float $coordAltOriginal = 0.0;

    public function toNEOriginal(): string
    {
        return MathUtils::toNE($this->coordLatOriginal, $this->coordLonOriginal);
    }
}


#region ObservacionesTrait
trait ObservacionesTrait
{
    public const COL_OBSERVACIONES = 'Observaciones';
    public const FLD_OBSERVACIONES = 'observaciones';

    #[ORM\Column(name: self::COL_OBSERVACIONES, type: DoctrineVar::STRING)]
    public string $observaciones = '';
}


#region AlturaTrait
trait AlturaTrait
{
    public const COL_ALTURA = 'Altura';
    public const FLD_ALTURA = 'altura';

    #[ORM\Column(name: self::COL_ALTURA, type: DoctrineVar::DECIMAL)]
    public float $altura = 0.0;
}


#region TelefonoTrait
trait TelefonoTrait
{
    public const COL_TELEFONO = 'Telefono';
    public const FLD_TELEFONO = 'telefono';

    #[ORM\Column(name: self::COL_TELEFONO, type: DoctrineVar::STRING)]
    public string $telefono = '';
}

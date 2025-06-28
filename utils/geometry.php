<?php

namespace TodoListGold\Utils\Geometry;

use Doctrine\Common\Collections\Collection;
use TodoListGold\Exceptions\NotImplementedException;
use TodoListGold\Utils\Dev\EmptyClass;
use TodoListGold\Utils\MathUtils;

enum GeoJSONType: string
{
    case POINT = 'Point';
    case LINE_STRING = 'LineString';
    case POLYGON = 'Polygon';
    case MULTI_POLYGON = 'MultiPolygon';
}


interface IArea
{
    public function contains(Point $point): bool;
    public function toCollection(): Collection;
}


interface IGeometry
{
    public static function getType(): GeoJsonType;
    public static function fromGeoJSON(array $coordinates): static;
    public function toJS(): string;
    public function toGeoJSON(): array;
}


interface IAreaGeometry extends IGeometry, IArea
{
}


class Point implements IGeometry
{
    public float $lat;
    public float $lon;

    public function __construct(float $lat, float $lon)
    {
        $this->lat = $lat;
        $this->lon = $lon;
    }

    public static function fromGeoJSON(array $coordinates): static
    {
        return new static($coordinates[1], $coordinates[0]);
    }

    public function distanceTo(self $other): float
    {
        return MathUtils::haversineFx([$this->lat, $this->lon], [$other->lat, $other->lon]);
    }

    /** @param Point[] $stack */
    public function getClosest(array $stack): ?self
    {
        $closest = null;
        $minDist = PHP_FLOAT_MAX;

        foreach ($stack as $punto) {
            $dist = $this->distanceTo($punto);
            if ($dist < $minDist) {
                $minDist = $dist;
                $closest = $punto;
            }
        }

        return $closest;
    }

    public static function getType(): GeoJsonType
    {
        return GeoJsonType::POINT;
    }

    public function toJS(): string
    {
        return "[{$this->lon}, {$this->lat}]";
    }

    public function toGeoJSON(): array
    {
        return [$this->lon, $this->lat];
    }
}


class LineString implements IGeometry
{
    /** @var Point[] */
    public array $points;

    public function __construct(array $points)
    {
        $this->points = $points;
    }

    public static function fromGeoJSON(array $coordinates): static
    {
        $points = [];
        foreach ($coordinates as $point) {
            $points[] = Point::fromGeoJSON($point);
        }

        return new static($points);
    }

    /**
     * Order of points is important
     * @param Point[] $points
     */
    public static function fromPoints(array $points): static
    {
        return new static($points);
    }

    public static function getType(): GeoJsonType
    {
        return GeoJsonType::LINE_STRING;
    }

    public function getDistance(): float
    {
        $distance = 0;
        $n = count($this->points);
        for ($i = 0; $i < $n - 1; $i++) {
            $distance += MathUtils::haversineFx($this->points[$i]->toGeoJSON(), $this->points[$i + 1]->toGeoJSON());
        }

        return $distance;
    }

    public function toJS(): string
    {
        $points = [];
        foreach ($this->points as $point) {
            $points[] = $point->toJS();
        }

        return '[' . implode(',', $points) . ']';
    }

    public function toGeoJSON(): array
    {
        $points = [];
        foreach ($this->points as $point) {
            $points[] = $point->toGeoJSON();
        }

        return $points;
    }
}


class Polygon implements IAreaGeometry
{
    /** @var Point[] */
    public array $points;

    public function __construct(array $points)
    {
        $this->points = $points;
    }

    public function contains(Point $point): bool
    {
        $contains = false;
        $n = count($this->points);
        $j = $n - 1;

        for ($i = 0; $i < $n; $i++) {
            if ($this->points[$i]->lat < $point->lat && $this->points[$j]->lat >= $point->lat || $this->points[$j]->lat < $point->lat && $this->points[$i]->lat >= $point->lat) {
                if ($this->points[$i]->lon + ($point->lat - $this->points[$i]->lat) / ($this->points[$j]->lat - $this->points[$i]->lat) * ($this->points[$j]->lon - $this->points[$i]->lon) < $point->lon) {
                    $contains = !$contains;
                }
            }
            $j = $i;
        }

        return $contains;
    }

    public static function fromGeoJSON(array $coordinates): static
    {
        $points = [];
        foreach ($coordinates[0] as $point) {
            $points[] = Point::fromGeoJSON($point);
        }

        return new static($points);
    }

    public static function getType(): GeoJsonType
    {
        return GeoJsonType::POLYGON;
    }

    public function toJS(): string
    {
        $points = [];
        foreach ($this->points as $point) {
            $points[] = $point->toJS();
        }

        return '[' . implode(',', $points) . ']';
    }

    public function toGeoJSON(): array
    {
        $points = [];
        foreach ($this->points as $point) {
            $points[] = $point->toGeoJSON();
        }

        return [$points];
    }

    public function toCollection(): Collection
    {
        throw new NotImplementedException();
    }
}


class MultiPolygon implements IAreaGeometry
{
    /** @var Polygon[] */
    public array $polygons;

    /**
     * @param Polygon[] $polygons
     */
    public function __construct(array $polygons)
    {
        $this->polygons = $polygons;
    }

    public function contains(Point $point): bool
    {
        foreach ($this->polygons as $polygon) {
            if ($polygon->contains($point)) {
                return true;
            }
        }

        return false;
    }

    public static function fromGeoJSON(array $coordinates): static
    {
        $polygons = [];
        foreach ($coordinates as $polygon) {
            $polygons[] = Polygon::fromGeoJSON($polygon);
        }

        return new static($polygons);
    }

    public static function getType(): GeoJsonType
    {
        return GeoJsonType::MULTI_POLYGON;
    }

    public function getUniquePolygon(): Polygon|MultiPolygon
    {
        if (count($this->polygons) === 1) {
            return $this->polygons[0];
        }

        return $this;
    }

    public function toJS(): string
    {
        $polygons = [];
        foreach ($this->polygons as $polygon) {
            $polygons[] = $polygon->toJS();
        }

        return '[' . implode(',', $polygons) . ']';
    }

    public function toGeoJSON(): array
    {
        $polygons = [];
        foreach ($this->polygons as $polygon) {
            $polygons[] = $polygon->toGeoJSON();
        }

        return $polygons;
    }

    public function toCollection(): Collection
    {
        throw new NotImplementedException();
    }
}


class GeoJSONWriter
{
    public const K_TYPE = 'type';
    public const K_FEATURES = 'features';
    public const K_GEOMETRY = 'geometry';
    public const K_PROPERTIES = 'properties';
    public const K_COORDINATES = 'coordinates';

    /**
     * @return string GeoJSON string
     */
    public static function createGeoJson(IGeometry ...$geometries): string
    {
        $features = [];
        foreach ($geometries as $geometry) {
            $features[] = [
                self::K_TYPE => 'Feature',
                self::K_PROPERTIES => new EmptyClass(),
                self::K_GEOMETRY => [
                    self::K_TYPE => $geometry::getType()->value,
                    self::K_COORDINATES => $geometry->toGeoJSON()
                ]
            ];
        }

        return json_encode([
            self::K_TYPE => 'FeatureCollection',
            self::K_FEATURES => $features
        ]);
    }
}

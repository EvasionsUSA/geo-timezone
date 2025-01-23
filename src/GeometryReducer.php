<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone;

use Tochka\GeoPHP\Geometry\GeometryCollection;
use Tochka\GeoPHP\Geometry\GeometryInterface;
use Tochka\GeoPHP\Geometry\LineString;
use Tochka\GeoPHP\Geometry\MultiPolygon;
use Tochka\GeoPHP\Geometry\Point;
use Tochka\GeoPHP\Geometry\Polygon;

readonly class GeometryReducer
{
    public static function reduceGeometryToMultiPolygon(GeometryInterface $geometry): Polygon|MultiPolygon
    {
        if ($geometry instanceof Polygon || $geometry instanceof MultiPolygon) {
            return $geometry;
        }

        if ($geometry instanceof GeometryCollection) {
            $polygons = [];
            foreach ($geometry->getComponents() as $component) {
                $polygons[] = self::reduceGeometryToPolygon($component);
            }

            return new MultiPolygon($polygons);
        }

        return self::reduceGeometryToPolygon($geometry);
    }

    public static function reduceGeometryToPolygon(GeometryInterface $geometry): Polygon
    {
        if ($geometry instanceof Polygon) {
            return $geometry;
        }

        if ($geometry instanceof Point) {
            $geometry = new LineString([$geometry, $geometry]);
        }

        if ($geometry instanceof LineString) {
            return new Polygon([$geometry]);
        }

        throw new \RuntimeException('Unknown geometry type');
    }
}

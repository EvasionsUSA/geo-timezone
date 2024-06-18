<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone;

use Tochka\GeoPHP\Geometry\GeometryCollection;
use Tochka\GeoPHP\Geometry\GeometryInterface;
use Tochka\GeoPHP\Geometry\LineString;
use Tochka\GeoPHP\Geometry\MultiPolygon;
use Tochka\GeoPHP\Geometry\Point;
use Tochka\GeoPHP\Geometry\Polygon;
use Tochka\GeoTimeZone\Exception\GeoTimeZoneException;
use Tochka\GeoTimeZone\Quadrant\Quadrant;
use Tochka\GeoTimeZone\Quadrant\TimezoneData;

readonly class TimezoneFilter
{
    /**
     * @param list<TimezoneData> $timezonesData
     * @return list<TimezoneData>
     * @throws GeoTimeZoneException
     */
    public function timezonesInQuadrant(array $timezonesData, Quadrant $quadrant): array
    {
        $timezones = [];
        $quadrantPolygon = $quadrant->getPolygon();

        try {
            foreach ($timezonesData as $timezoneData) {
                if (!$quadrantPolygon->intersects($timezoneData->geometry)) {
                    continue;
                }

                $geometry = $timezoneData->geometry->intersection($quadrantPolygon);
                if ($geometry === null) {
                    continue;
                }

                $timezones[] = new TimezoneData($timezoneData->timezone, $this->reduceGeometryToMultiPolygon($geometry));
            }

            return $timezones;
        } catch (\Throwable $e) {
            throw new GeoTimeZoneException($e->getMessage(), 1001, $e);
        }
    }

    private function reduceGeometryToMultiPolygon(GeometryInterface $geometry): Polygon|MultiPolygon
    {
        if ($geometry instanceof Polygon || $geometry instanceof MultiPolygon) {
            return $geometry;
        }

        if ($geometry instanceof GeometryCollection) {
            $polygons = [];
            foreach ($geometry->getComponents() as $component) {
                $polygons[] = $this->reduceGeometryToPolygon($component);
            }

            return new MultiPolygon($polygons);
        }

        return $this->reduceGeometryToPolygon($geometry);
    }

    private function reduceGeometryToPolygon(GeometryInterface $geometry): Polygon
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

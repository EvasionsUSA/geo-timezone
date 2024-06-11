<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone\Geometry;

use Tochka\GeoPHP\Geometry\GeometryInterface;
use Tochka\GeoPHP\Geometry\Point;
use Tochka\GeoPHP\GeoPHP;

/**
 * @api
 */
readonly class Utils
{
    public const POLYGON_GEOJSON_NAME = "Polygon";
    public const POINT_WKT_NAME = "POINT";
    public const FEATURE_COLLECTION_GEOJSON_NAME = "FeatureCollection";
    public const FEATURE_GEOJSON_NAME = "Feature";
    public const WKT_EXTENSION = "wkt";
    public const GEOJSON_EXTENSION = "json";
    public const NOT_FOUND_IN_FEATURES = "notFoundInFeatures";

    /**
     * Convert array of coordinates to polygon structured json array
     * @param $polygonPoints
     * @return array
     */
    public function createPolygonJsonFromPoints($polygonPoints)
    {
        return [
            'type' => self::POLYGON_GEOJSON_NAME,
            'coordinates' => $this->structurePolygonCoordinates($polygonPoints),
        ];
    }

    /**
     * Structure polygon coordinates as geoPHP needs
     * @param $polygonPoints
     * @return array
     */
    protected function structurePolygonCoordinates($polygonPoints)
    {
        $structuredCoordinates = [];
        foreach ($polygonPoints as $points) {
            if (count($points) == 2) {
                $structuredCoordinates[] = $polygonPoints;
                break;
            }
            $structuredCoordinates[] = $points;
        }
        return $structuredCoordinates;
    }

    /**
     * Create polygon geometry object from polygon points array
     */
    protected function createPolygonFromPoints($polygonPoints): ?GeometryInterface
    {
        $polygonData = $this->createPolygonJsonFromPoints($polygonPoints);
        return $this->createPolygonFromJson(json_encode($polygonData));
    }

    /**
     * Create polygon geometry object from structured polygon data (as json)
     */
    public function createPolygonFromJson(string $polygonJson): ?GeometryInterface
    {
        return GeoPHP::load($polygonJson, self::GEOJSON_EXTENSION);
    }

    /**
     * Adapt quadrant bounds to polygon array format
     * @param $quadrantBounds
     * @return array
     */
    public function adaptQuadrantBoundsToPolygon($quadrantBounds)
    {
        return [
            [
                [$quadrantBounds[0], $quadrantBounds[1]],
                [$quadrantBounds[0], $quadrantBounds[3]],
                [$quadrantBounds[2], $quadrantBounds[3]],
                [$quadrantBounds[2], $quadrantBounds[1]],
                [$quadrantBounds[0], $quadrantBounds[1]],
            ],
        ];
    }

    /**
     * Create polygon object from quadrant bounds
     * @param $quadrantBounds
     * @return mixed
     */
    public function getQuadrantPolygon($quadrantBounds)
    {
        $polygonPoints = $this->adaptQuadrantBoundsToPolygon($quadrantBounds);
        return $this->createPolygonFromPoints($polygonPoints);
    }

    /**
     * Structure features data
     * @param $features
     * @return array
     */
    private function structureFeatures($features)
    {
        $structuredFeatures = [];
        foreach ($features as $feature) {
            $structuredFeatures[] = $this->structureOneFeature($feature);
        }
        return $structuredFeatures;
    }

    /**
     * Structure an isolated feature
     * @param $feature
     * @return array
     */
    private function structureOneFeature($feature)
    {
        $structuredFeature = [
            "type" => self::FEATURE_GEOJSON_NAME,
            "geometry" => [
                "type" => $feature['type'],
                "coordinates" => $feature['coordinates'],
            ],
            "properties" => $feature['properties'],
        ];
        return $structuredFeature;
    }

    /**
     * Create feature collection array from features list
     * @param $features
     * @return array
     */
    public function getFeatureCollection($features)
    {
        $featuresCollection = [
            "type" => self::FEATURE_COLLECTION_GEOJSON_NAME,
            "features" => $this->structureFeatures($features),
        ];
        return $featuresCollection;
    }

    /**
     * Get intersection data json from two different geometry features
     * @param $geoFeaturesJsonA
     * @param $geoFeaturesJsonB
     * @return mixed
     */
    public function intersection($geoFeaturesJsonA, $geoFeaturesJsonB)
    {
        $polygonA = $this->createPolygonFromJson($geoFeaturesJsonA);
        $polygonB = $this->createPolygonFromJson($geoFeaturesJsonB);
        $intersectionData = $polygonA->intersection($polygonB);
        return $intersectionData->out(self::GEOJSON_EXTENSION, true);
    }

    /**
     * Check if a particular object point is IN the indicated polygon (source: https://github.com/sookoll/geoPHP.git)
     * and if it is not contained inside, it checks the boundaries
     * @param $point
     * @param $polygon
     * @return mixed
     */
    private function isInPolygon($point, $polygon)
    {
        $isInside = false;
        foreach ($polygon->components as $component) {
            $polygonPoints = $component->getComponents();
            $numPoints = count($polygonPoints);
            $pointIdxBack = $numPoints - 1;
            for ($pointIdx = 0; $pointIdx < $numPoints; $pointIdx++) {
                if ($this->isInside($point, $polygonPoints[$pointIdx], $polygonPoints[$pointIdxBack])) {
                    $isInside = true;
                    break;
                }
                $pointIdxBack = $pointIdx;
            }
        }
        return $isInside;
    }

    /**
     * Check if point is ON the boundaries of the polygon
     * @param $point
     * @param $polygon
     * @return mixed
     */
    private function isOnPolygonBoundaries($point, $polygon)
    {
        return $polygon->pointOnVertex($point);
    }

    /**
     * Check if the polygonA intersects with polygonB
     * @param $polygonJsonA
     * @param $polygonBoundsB
     * @return mixed
     * @internal param $polygonA
     * @internal param $polygonB
     */
    public function intersectsPolygons($polygonJsonA, $polygonBoundsB)
    {
        $polygonA = $this->createPolygonFromJson(json_encode($polygonJsonA));
        $polygonB = $this->getQuadrantPolygon($polygonBoundsB);
        return $polygonA->intersects($polygonB);
    }

    /**
     * Check if the polygonA is within polygonB
     * @param $polygonBoundsOrigin
     * @param $polygonJsonDest
     * @return mixed
     * @internal param $polygonA
     * @internal param $polygonB
     */
    public function withinPolygon($polygonBoundsOrigin, $polygonJsonDest)
    {
        $polygonDest = $this->createPolygonFromJson(json_encode($polygonJsonDest));
        $polygonOrig = $this->getQuadrantPolygon($polygonBoundsOrigin);
        return $polygonOrig->within($polygonDest);
    }

    /**
     * Create a point geometry object from coordinates (latitude, longitude)
     */
    private function createPoint($latitude, $longitude): Point
    {
        return new Point($longitude, $latitude);
    }

    /**
     * Check if point (latitude, longitude) is IN a particular features polygon
     */
    public function isPointInQuadrantFeatures($features, float $latitude, float $longitude): string
    {
        $timeZone = self::NOT_FOUND_IN_FEATURES;
        $point = $this->createPoint($latitude, $longitude);
        if ($point != null) {
            foreach ($features['features'] as $feature) {
                foreach ($feature['geometry']['coordinates'] as $polygonFeatures) {
                    $polygon = $this->createPolygonFromJson(
                        json_encode($this->createPolygonJsonFromPoints(
                            $polygonFeatures,
                        )),
                    );
                    if ($this->isInPolygon($point, $polygon) ||
                        $this->isOnPolygonBoundaries($point, $polygon)) {
                        $timeZone = $feature['properties']['tzid'];
                        break;
                    }
                }
            }
        }
        return $timeZone;
    }

    /**
     * Check if the point is between two points from the polygon
     * @param $point
     * @param $currentPolygonPoint
     * @param $backPolygonPoint
     * @return bool
     */
    private function isInside(Point $point, $currentPolygonPoint, $backPolygonPoint): bool
    {
        return ($currentPolygonPoint->y() > $point->y()) != ($backPolygonPoint->y() > $point->y()) &&
            (
                $point->x() < ($backPolygonPoint->x() - $currentPolygonPoint->x()) *
                ($point->y() - $currentPolygonPoint->y()) / ($backPolygonPoint->y() - $currentPolygonPoint->y()) +
                $currentPolygonPoint->x()
            );
    }
}

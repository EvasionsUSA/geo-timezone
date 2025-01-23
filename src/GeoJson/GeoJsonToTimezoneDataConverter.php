<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone\GeoJson;

use Tochka\GeoPHP\Adapters\GeoJSON;
use Tochka\GeoPHP\Geometry\MultiPolygon;
use Tochka\GeoPHP\Geometry\Polygon;
use Tochka\GeoTimeZone\Exception\InvalidGeoJsonData;
use Tochka\GeoTimeZone\Quadrant\TimezoneData;

readonly class GeoJsonToTimezoneDataConverter
{
    public function __construct(
        private GeoJSON $geoJSON = new GeoJSON(),
    ) {}

    /**
     * @param string $json
     * @return list<TimezoneData>
     * @throws InvalidGeoJsonData
     */
    public function fromGeoJsonToTimezoneData(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new InvalidGeoJsonData('Invalid json', 2002, $e);
        }

        if (!isset($data['type']) || $data['type'] !== 'FeatureCollection' || !isset($data['features']) || !is_array(
            $data['features'],
        )) {
            throw new InvalidGeoJsonData('Invalid GeoJSON format');
        }

        return $this->parseFeatureCollection($data['features']);
    }

    /**
     * @param list<TimezoneData> $timezonesData
     * @throws InvalidGeoJsonData
     */
    public function fromTimezoneDataToGeoJson(array $timezonesData): string
    {
        $geoJson = ['type' => 'FeatureCollection', 'features' => []];

        try {
            foreach ($timezonesData as $timezoneData) {
                $geoJson['features'][] = [
                    'type' => 'Feature',
                    'properties' => ['tzid' => $timezoneData->timezone],
                    'geometry' => $this->geoJSON->write($timezoneData->geometry, true),
                ];
            }

            return json_encode($geoJson, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new InvalidGeoJsonData('Error while write GeoJSON', 2001, $e);
        }
    }

    /**
     * @param array $featureCollection
     * @return list<TimezoneData>
     */
    private function parseFeatureCollection(array $featureCollection): array
    {
        $result = [];
        foreach ($featureCollection as $feature) {
            $result[] = $this->parseFeature($feature);
        }

        return $result;
    }

    public function parseFeature(array $feature): TimezoneData
    {
        if (!isset($feature['type']) || $feature['type'] !== 'Feature' || !isset($feature['properties']['tzid']) || !isset($feature['geometry'])) {
            throw new InvalidGeoJsonData('Invalid GeoJSON format');
        }
        $geometry = $this->geoJSON->read($feature['geometry']);
        if (!$geometry instanceof Polygon && !$geometry instanceof MultiPolygon) {
            throw new InvalidGeoJsonData('Invalid GeoJSON geometry type');
        }

        return new TimezoneData($feature['properties']['tzid'], $geometry);
    }
}

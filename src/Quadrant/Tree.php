<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone\Quadrant;

use ErrorException;
use Tochka\GeoTimeZone\Geometry\Utils;

class Tree
{
    public const DATA_TREE_FILENAME = 'index.json';
    public const GEO_FEATURE_FILENAME = 'geo.json';
    public const NONE_TIMEZONE = 'none';

    private readonly array $dataTree;
    private readonly string $dataDirectory;

    /**
     * @throws ErrorException
     * @throws \JsonException
     */
    public function __construct(
        string $dataDirectory,
        private readonly Utils $utils = new Utils(),
    ) {
        $this->dataDirectory = trim($dataDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $dataTreeFileName = $this->dataDirectory . self::DATA_TREE_FILENAME;

        if (!is_dir($dataDirectory) || !file_exists($dataTreeFileName)) {
            throw new ErrorException('Invalid data directory: ' . $dataDirectory);
        }
        $jsonData = file_get_contents($dataTreeFileName);
        $this->dataTree = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Load json features data from a particular geo quadrant path
     * @throws \JsonException
     */
    protected function loadFeatures(string $quadrantPath): array
    {
        $subDirectory = implode(DIRECTORY_SEPARATOR, str_split($quadrantPath));
        $filePath = $this->dataDirectory . $subDirectory . DIRECTORY_SEPARATOR . self::GEO_FEATURE_FILENAME;

        return json_decode(file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Check if a particular location (latitude, longitude)is IN a particular quadrant
     * @throws \JsonException
     */
    protected function evaluateFeatures(string $quadrantPath, float $latitude, float $longitude): string
    {
        $features = $this->loadFeatures($quadrantPath);
        return $this->utils->isPointInQuadrantFeatures($features, $latitude, $longitude);
    }

    /**
     * Get valid timezone
     * @throws ErrorException
     * @throws \JsonException
     */
    private function evaluateQuadrantData(string $zoneData, string $quadrantPath, float $latitude, float $longitude): string
    {
        $validTimezone = self::NONE_TIMEZONE;
        if (!isset($zoneData)) {
            throw new ErrorException('Unexpected data type');
        } elseif ($zoneData === 'f') {
            $validTimezone = $this->evaluateFeatures($quadrantPath, $latitude, $longitude);
        } elseif (is_numeric($zoneData)) {
            $validTimezone = $this->dataTree['timezones'][$zoneData];
        }
        return $validTimezone;
    }

    /**
     * Check if timezone is valid
     */
    protected function isValidTimeZone($timeZone): bool
    {
        return $timeZone !== self::NONE_TIMEZONE;
    }

    /**
     * Main function for looking the timezone associated to a particular location (latitude, longitude)
     * @throws ErrorException
     * @throws \JsonException
     */
    public function lookForTimeZone(float $latitude, float $longitude): string
    {
        $geoQuadrant = new Element();
        $timeZone = self::NONE_TIMEZONE;
        $quadrantPath = '';
        $quadrantTree = $this->dataTree['lookup'];

        while (!$this->isValidTimeZone($timeZone)) {
            $geoQuadrant = $geoQuadrant->moveToNextQuadrant($latitude, $longitude);
            if (!isset($quadrantTree[$geoQuadrant->getLevel()])) {
                break;
            }
            $quadrantTree = $quadrantTree[$geoQuadrant->getLevel()];
            $quadrantPath = $quadrantPath . $geoQuadrant->getLevel();
            $timeZone = $this->evaluateQuadrantData($quadrantTree, $quadrantPath, $latitude, $longitude);
            $geoQuadrant = $geoQuadrant->updateMidCoordinates();
        }

        if ($timeZone === self::NONE_TIMEZONE || $timeZone === Utils::NOT_FOUND_IN_FEATURES) {
            throw new ErrorException("ERROR: TimeZone not found");
        }

        return $timeZone;
    }
}

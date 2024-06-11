<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone;

use Tochka\GeoTimeZone\Quadrant\Element;
use Tochka\GeoTimeZone\Quadrant\Tree;

/**
 * @api
 */
readonly class Calculator
{
    private Tree $quadrantTree;

    /**
     * @throws \ErrorException
     * @throws \JsonException
     */
    public function __construct(string $dataDirectory)
    {
        $this->quadrantTree = new Tree($dataDirectory);
    }

    /**
     * Adjust the latitude value
     * @throws \ErrorException
     */
    private function adjustLatitude(float|null $latitude): float
    {
        $newLatitude = $latitude;
        if (null == $latitude || abs($latitude) > Element::MAX_ABS_LATITUDE) {
            throw new \ErrorException('Invalid latitude: ' . $latitude);
        }
        if (abs($latitude) == Element::MAX_ABS_LATITUDE) {
            $newLatitude = ($latitude <=> 0) * Element::ABS_LATITUDE_LIMIT;
        }
        return $newLatitude;
    }

    /**
     * Adjust longitude value
     * @throws \ErrorException
     */
    protected function adjustLongitude(float|null $longitude): float
    {
        $newLongitude = $longitude;
        if (null == $longitude || abs($longitude) > Element::MAX_ABS_LONGITUDE) {
            throw new \ErrorException('Invalid longitude: ' . $longitude);
        }
        if (abs($longitude) == Element::MAX_ABS_LONGITUDE) {
            $newLongitude = ($longitude <=> 0) * Element::ABS_LONGITUDE_LIMIT;
        }
        return $newLongitude;
    }

    /**
     * Get timezone name from a particular location (latitude, longitude)
     * @throws \ErrorException
     */
    public function getTimeZoneName(float $latitude, float $longitude): string
    {
        $latitude = $this->adjustLatitude($latitude);
        $longitude = $this->adjustLongitude($longitude);

        return $this->quadrantTree->lookForTimezone($latitude, $longitude);
    }

    /**
     * Get the local date belonging to a particular latitude, longitude and timestamp
     * @throws \ErrorException
     * @throws \Exception
     */
    public function getLocalDate(float $latitude, float $longitude, int $timestamp): \DateTime
    {
        $date = new \DateTime();
        $timeZone = $this->getTimeZoneName($latitude, $longitude);
        $date->setTimestamp($timestamp);
        if ($timeZone !== Tree::NONE_TIMEZONE) {
            $date->setTimezone(new \DateTimeZone($timeZone));
        }
        return $date;
    }

    /**
     * Get timestamp from latitude, longitude and localTimestamp
     * @throws \ErrorException
     * @throws \Exception
     */
    public function getCorrectTimestamp(float $latitude, float $longitude, int $localTimestamp): int
    {
        $timestamp = $localTimestamp;
        $timeZoneName = $this->getTimeZoneName($latitude, $longitude);
        if ($timeZoneName != Tree::NONE_TIMEZONE) {
            $date = new \DateTime();
            $date->setTimestamp($localTimestamp);
            if ($timeZoneName != null) {
                $date->setTimezone(new \DateTimeZone($timeZoneName));
            }
            $timestamp = $localTimestamp - $date->getOffset();
        }

        return $timestamp;
    }
}

<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone\Quadrant;

readonly class Element
{
    public const MAX_ABS_LATITUDE = 90.0;
    public const MAX_ABS_LONGITUDE = 180.0;
    public const ABS_LATITUDE_LIMIT = 89.9999;
    public const ABS_LONGITUDE_LIMIT = 179.9999;
    public const LEVEL_A = 'a';
    public const LEVEL_B = 'b';
    public const LEVEL_C = 'c';
    public const LEVEL_D = 'd';

    public function __construct(
        private float  $top = self::MAX_ABS_LATITUDE,
        private float  $bottom = (-1) * self::MAX_ABS_LATITUDE,
        private float  $left = (-1) * self::MAX_ABS_LONGITUDE,
        private float  $right = self::MAX_ABS_LONGITUDE,
        private float  $midLat = 0.0,
        private float  $midLon = 0.0,
        private string $level = self::LEVEL_A,
    ) {}

    /**
     * Move the current quadrant to a particular location (latitude, longitude)
     */
    public function moveToNextQuadrant(float $latitude, float $longitude): Element
    {
        $top = $this->top;
        $bottom = $this->bottom;
        $left = $this->left;
        $right = $this->right;

        if ($latitude >= $this->midLat) {
            $bottom = $this->midLat;
            if ($longitude >= $this->midLon) {
                $level = self::LEVEL_A;
                $left = $this->midLon;
            } else {
                $level = self::LEVEL_B;
                $right = $this->midLon;
            }
        } elseif ($longitude < $this->midLon) {
            $level = self::LEVEL_C;
            $top = $this->midLat;
            $right = $this->midLon;
        } else {
            $level = self::LEVEL_D;
            $top = $this->midLat;
            $left = $this->midLon;
        }

        return new self($top, $bottom, $left, $right, $this->midLat, $this->midLon, $level);
    }

    /**
     * Update the mid coordinates attributes of the quadrant
     */
    public function updateMidCoordinates(): Element
    {
        $midLat = ($this->top + $this->bottom) / 2.0;
        $midLon = ($this->left + $this->right) / 2.0;

        return new self($this->top, $this->bottom, $this->left, $this->right, $midLat, $midLon, $this->level);
    }

    /**
     * Get the quadrant level (a, b, c or d)
     */
    public function getLevel(): string
    {
        return $this->level;
    }
}

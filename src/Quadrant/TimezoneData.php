<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone\Quadrant;

use Tochka\GeoPHP\Geometry\MultiPolygon;
use Tochka\GeoPHP\Geometry\Polygon;

readonly class TimezoneData
{
    public function __construct(
        public string $timezone,
        public Polygon|MultiPolygon $geometry,
    ) {}
}

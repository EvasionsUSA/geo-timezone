<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone\Quadrant;

use Tochka\GeoPHP\Geometry\LineString;
use Tochka\GeoPHP\Geometry\Point;
use Tochka\GeoPHP\Geometry\Polygon;

readonly class Quadrant
{
    public function __construct(
        public float $topLeftX,
        public float $topLeftY,
        public float $bottomRightX,
        public float $bottomRightY,
        public ?string $id = null,
    ) {}

    public function getPolygon(): Polygon
    {
        return new Polygon([
            new LineString([
                new Point($this->topLeftX, $this->topLeftY),
                new Point($this->bottomRightX, $this->topLeftY),
                new Point($this->bottomRightX, $this->bottomRightY),
                new Point($this->topLeftX, $this->bottomRightY),
                new Point($this->topLeftX, $this->topLeftY),
            ]),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone;

use Psr\Log\LoggerInterface;
use Tochka\GeoPHP\Geometry\Point;
use Tochka\GeoTimeZone\DataRepository\DataRepositoryInterface;
use Tochka\GeoTimeZone\Quadrant\QuadrantBuilder;

/**
 * @api
 */
readonly class TimezoneFinder
{
    public function __construct(
        private DataRepositoryInterface $dataRepository,
        private QuadrantBuilder $quadrantBuilder = new QuadrantBuilder(),
        private ?LoggerInterface $logger = null,
    ) {}

    public function findTimezone(float $latitude, float $longitude, int $indexLevel = 0): ?string
    {
        $this->logger?->info('Find index for coordinates');
        $quadrantIndex = $this->quadrantBuilder->getQuadrantIndexByPoint($latitude, $longitude, $indexLevel);

        $this->logger?->info('Load data for quadrant [' . ($quadrantIndex ?? 'default') . ']');
        $data = $this->dataRepository->read(empty($quadrantIndex) ? null : $quadrantIndex);

        $point = new Point($longitude, $latitude);

        $this->logger?->info('Search in quadrant...');
        foreach ($data as $timezoneData) {
            try {
                if ($point->intersects($timezoneData->geometry)) {
                    return $timezoneData->timezone;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}

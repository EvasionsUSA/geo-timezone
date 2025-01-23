<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone;

use Psr\Log\LoggerInterface;
use Tochka\GeoTimeZone\DataRepository\DataRepositoryInterface;
use Tochka\GeoTimeZone\Exception\GeoTimeZoneException;
use Tochka\GeoTimeZone\Quadrant\Quadrant;
use Tochka\GeoTimeZone\Quadrant\QuadrantBuilder;
use Tochka\GeoTimeZone\Quadrant\TimezoneData;

/**
 * @api
 * @deprecated Use \Tochka\GeoTimeZone\IndexerWithOptimalReader
 */
readonly class Indexer
{
    public const DEFAULT_INDEX_LEVEL = 6;

    public function __construct(
        private DataRepositoryInterface $dataRepository,
        private QuadrantBuilder $quadrantBuilder = new QuadrantBuilder(),
        private ?LoggerInterface $logger = null,
    ) {}

    public function index(int $level = self::DEFAULT_INDEX_LEVEL, ?Quadrant $quadrant = null): void
    {
        if ($level === 0) {
            return;
        }

        if ($quadrant === null) {
            $quadrant = $this->quadrantBuilder->getDefaultQuadrant();
        }

        $this->logger?->info('Loading data for quadrant [' . ($quadrant->id ?? 'default') . ']');
        $data = $this->dataRepository->read($quadrant->id);

        $quadrants = [
            $this->quadrantBuilder->getQuadrantById($quadrant, QuadrantBuilder::INDEX_A),
            $this->quadrantBuilder->getQuadrantById($quadrant, QuadrantBuilder::INDEX_B),
            $this->quadrantBuilder->getQuadrantById($quadrant, QuadrantBuilder::INDEX_C),
            $this->quadrantBuilder->getQuadrantById($quadrant, QuadrantBuilder::INDEX_D),
        ];

        foreach ($quadrants as $subQuadrant) {
            $this->logger?->info('Lookup timezones in quadrant [' . ($subQuadrant->id ?? 'default') . ']');
            $filteredTimezones = $this->timezonesInQuadrant($data, $subQuadrant);

            $this->logger?->info('Found [' . count($filteredTimezones) . '] timezones. Saving...');
            $this->dataRepository->write($filteredTimezones, $subQuadrant->id);
        }

        foreach ($quadrants as $subQuadrant) {
            if ($level - 1 > 0) {
                $this->index($level - 1, $subQuadrant);
                $this->logger?->info('Delete index for quadrant [' . ($quadrant->id ?? 'default') . ']');
                $this->dataRepository->remove($subQuadrant->id);
            }
        }
    }

    /**
     * @param list<TimezoneData> $timezonesData
     * @return list<TimezoneData>
     * @throws GeoTimeZoneException
     */
    private function timezonesInQuadrant(array $timezonesData, Quadrant $quadrant): array
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

                $timezones[] = new TimezoneData($timezoneData->timezone, GeometryReducer::reduceGeometryToMultiPolygon($geometry));
            }

            return $timezones;
        } catch (\Throwable $e) {
            throw new GeoTimeZoneException($e->getMessage(), 1001, $e);
        }
    }
}

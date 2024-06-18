<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone;

use Psr\Log\LoggerInterface;
use Tochka\GeoTimeZone\DataRepository\DataRepositoryInterface;
use Tochka\GeoTimeZone\Quadrant\Quadrant;
use Tochka\GeoTimeZone\Quadrant\QuadrantBuilder;

/**
 * @api
 */
readonly class Indexer
{
    public function __construct(
        private DataRepositoryInterface $dataRepository,
        private QuadrantBuilder $quadrantBuilder = new QuadrantBuilder(),
        private TimezoneFilter $timezonesFinder = new TimezoneFilter(),
        private ?LoggerInterface $logger = null,
    ) {}

    public function index(int $level, ?Quadrant $quadrant = null): void
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
            $filteredTimezones = $this->timezonesFinder->timezonesInQuadrant($data, $subQuadrant);

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
}

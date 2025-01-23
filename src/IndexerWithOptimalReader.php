<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone;

use JsonStreamingParser\Listener\GeoJsonListener;
use JsonStreamingParser\Parser;
use Psr\Log\LoggerInterface;
use Tochka\GeoTimeZone\DataRepository\DataRepositoryInterface;
use Tochka\GeoTimeZone\GeoJson\GeoJsonToTimezoneDataConverter;
use Tochka\GeoTimeZone\Quadrant\Quadrant;
use Tochka\GeoTimeZone\Quadrant\QuadrantBuilder;
use Tochka\GeoTimeZone\Quadrant\TimezoneData;

/**
 * @api
 */
readonly class IndexerWithOptimalReader
{
    public const DEFAULT_INDEX_LEVEL = 6;

    public function __construct(
        private string $geoJsonPath,
        private DataRepositoryInterface $dataRepository,
        private QuadrantBuilder $quadrantBuilder = new QuadrantBuilder(),
        private GeoJsonToTimezoneDataConverter $geoJsonConverter = new GeoJsonToTimezoneDataConverter(),
        private ?LoggerInterface $logger = null,
    ) {}

    public function index(int $level = self::DEFAULT_INDEX_LEVEL): void
    {
        $indexQuadrants = $this->quadrantBuilder->getQuadrantsByLevel($level);
        foreach ($indexQuadrants as $quadrant) {
            $this->dataRepository->write([], $quadrant->id);
        }

        $stream = fopen($this->geoJsonPath, 'r');
        $listener = new GeoJsonListener(fn(array $feature) => $this->parseTimezone($feature, $indexQuadrants));

        try {
            $parser = new Parser($stream, $listener);
            $parser->parse();
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param array $feature
     * @param list<Quadrant> $quadrants
     * @return void
     * @throws \Exception
     */
    private function parseTimezone(array $feature, array $quadrants): void
    {
        $timezoneData = $this->geoJsonConverter->parseFeature($feature);

        $this->logger?->info('Make index for timezone [' . $timezoneData->timezone . ']');

        $this->makeIndex($timezoneData, $quadrants);
    }

    /**
     * @param TimezoneData $timezoneData
     * @param list<Quadrant> $quadrants
     * @return void
     * @throws \Exception
     */
    private function makeIndex(TimezoneData $timezoneData, array $quadrants): void
    {
        foreach ($quadrants as $quadrant) {
            $quadrantPolygon = $quadrant->getPolygon();

            if (!$timezoneData->geometry->intersects($quadrantPolygon)) {
                return;
            }

            $timezonePolygonInQuadrant = $timezoneData->geometry->intersection($quadrantPolygon);
            if ($timezonePolygonInQuadrant === null) {
                return;
            }

            $data = $this->dataRepository->read($quadrant->id);
            $data[] = new TimezoneData(
                $timezoneData->timezone,
                GeometryReducer::reduceGeometryToMultiPolygon($timezonePolygonInQuadrant),
            );

            $this->dataRepository->write($data, $quadrant->id);
        }
    }
}

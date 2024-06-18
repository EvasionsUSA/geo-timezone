<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone\DataRepository;

use Tochka\GeoTimeZone\Exception\FileNotFoundException;
use Tochka\GeoTimeZone\Exception\WriteIndexException;
use Tochka\GeoTimeZone\GeoJson\GeoJsonToTimezoneDataConverter;
use Tochka\GeoTimeZone\Quadrant\TimezoneData;

/**
 * @api
 */
readonly class JsonFileDataRepository implements DataRepositoryInterface
{
    public function __construct(
        private string $baseGeoDataPath,
        private string $dataDirectory,
        private GeoJsonToTimezoneDataConverter $geoJsonConverter = new GeoJsonToTimezoneDataConverter(),
    ) {}

    public function has(?string $indexName = null): bool
    {
        return file_exists($this->getIndexFilePath($indexName));
    }

    /**
     * @return list<TimezoneData>
     */
    public function read(?string $indexName = null): array
    {
        $fileName = $this->getIndexFilePath($indexName);
        if (!file_exists($fileName)) {
            throw new FileNotFoundException('Not found index file: ' . $fileName);
        }

        return $this->geoJsonConverter->fromGeoJsonToTimezoneData(file_get_contents($fileName));
    }

    /**
     * @param list<TimezoneData> $data
     */
    public function write(array $data, ?string $indexName = null): void
    {
        if ($indexName === null) {
            return;
        }

        $geoJson = $this->geoJsonConverter->fromTimezoneDataToGeoJson($data);

        try {
            file_put_contents($this->getIndexFilePath($indexName), $geoJson);
        } catch (\Throwable $e) {
            throw new WriteIndexException($e->getMessage(), 3001, $e);
        }
    }

    public function remove(?string $indexName = null): void
    {
        if ($indexName === null) {
            return;
        }

        @unlink($this->getIndexFilePath($indexName));
    }

    private function getIndexFilePath(?string $indexName): string
    {
        $indexDirectory = rtrim($this->dataDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'timezones';

        if (!is_dir($indexDirectory)) {
            mkdir($indexDirectory, recursive: true);
        }

        return $indexName === null ? $this->baseGeoDataPath : $indexDirectory . DIRECTORY_SEPARATOR . $indexName . '.json';
    }
}

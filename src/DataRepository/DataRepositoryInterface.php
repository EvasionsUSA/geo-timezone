<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone\DataRepository;

use Tochka\GeoTimeZone\Quadrant\TimezoneData;

interface DataRepositoryInterface
{
    public function has(?string $indexName = null): bool;

    /**
     * @return list<TimezoneData>
     */
    public function read(?string $indexName = null): array;

    /**
     * @param list<TimezoneData> $data
     */
    public function write(array $data, ?string $indexName = null): void;

    public function remove(?string $indexName = null): void;
}

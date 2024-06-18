#!/usr/bin/env php
<?php

declare(strict_types=1);

use Tochka\GeoTimeZone\UpdaterData;

require __DIR__ . '/../vendor/autoload.php';

$updater = new UpdaterData(__DIR__ . '/../data/geo.data');
$updater->updateData();

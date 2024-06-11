#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use src\UpdaterData;

$updater = new UpdaterData(__DIR__ . '/../data/geo.data');
$updater->updateData();

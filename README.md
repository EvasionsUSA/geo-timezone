# Geo-Timezone PHP Library
[![Build Status](https://travis-ci.org/minube/geo-timezone.png)](https://travis-ci.org/minube/geo-timezone) [![Code Coverage](https://scrutinizer-ci.com/g/minube/geo-timezone/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/minube/geo-timezone/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/minube/geo-timezone/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/minube/geo-timezone/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/minube/geo-timezone/badges/build.png?b=master)](https://scrutinizer-ci.com/g/minube/geo-timezone/build-status/master)

Based on the [node-geo-tz package](https://github.com/evansiroky/node-geo-tz), this PHP library calculates the timezone associated to a particular location based on coordinates (latitude, longitude) and timestamp
reference. Therefore, this library provides the local date of a particular location in any moment too.
In addition, it is based on the timezones boundaries extracted by [timezone-boundary-builder](https://github.com/evansiroky/timezone-boundary-builder) tool, so it is necessary to always use the latest version of this package.
In order to improve the timezone search through the boundaries, node-geo-tz proposes to create a tree of directories based on dividing the Earth in quadrants and sub-quadrants iteratively. This tree is called "data.zip" in the reference library.


## Requirements
GEOS PHP extension is needed to run library. So, you should download and compile it running the script bin/compile-geos
.sh; then, the library called "geos.so" will be added to /usr/lib/php.
As you can see, this script contains the installation of some php extensions that will be necessary in the next
step of the installation process.

Once you have compiled the GEOS PHP extension, you should create the file geos.ini in order to enable the module and improve the performance consequently.

Finally, you should run the composer file, so the rest of necessary libraries will be installed.


## Usage
There are two main classes:

* UpdaterData: script that downloads the last version of the timezone boundaries data and creates the tree of directories (data.zip). It takes a few hours, so you can use "data.zip" from node-geo-tz to test for the first time. Otherwise, you can run the UpdaterData script in order to get the last version and create the directories tree. Destination folder must have write permisions

```php
use Tochka\GeoTimeZone\UpdaterData;
use Tochka\GeoTimeZone\Indexer;

$updater = new UpdaterData('/path/to/base/data/');
$dataPath = $updater->updateData();

$dataRepository = new JsonFileDataRepository($dataPath, '/path/to/index/data/directory');
$indexer = new Indexer($dataRepository);
$indexer->index();
```

* Calculator: provides the timezone name or the local date associated to a particular latitude, longitude and timestamp.

```php
    use Tochka\GeoTimeZone\TimezoneFinder;
    use Tochka\GeoTimeZone\DataRepository\JsonFileDataRepository;

    $latitude = 39.452800;
    $longitude = -0.347038;
    $timestamp = 1469387760;

    $dataRepository = new JsonFileDataRepository('/path/to/base/data/file.json', '/path/to/index/data/');
    $timezoneFinder = new TimezoneFinder($dataRepository);

    // TimeZone name
    $timeZoneName = $timezoneFinder->findTimezone($latitude, $longitude);
    //Europe/Madrid
```

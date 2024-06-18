<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Tochka\GeoTimeZone\Exception\GeoTimeZoneException;
use Tochka\GeoTimeZone\Exception\UpdaterException;

/**
 * @api
 */
readonly class UpdaterData
{
    public const DOWNLOAD_DIR = 'downloads';
    public const TIMEZONE_FILE_NAME = 'timezones';
    public const REPO_HOST = 'https://api.github.com';
    public const REPO_USER = 'node-geo-tz';
    public const REPO_PATH = '/repos/evansiroky/timezone-boundary-builder/releases/latest';
    public const GEO_JSON_DEFAULT_URL = 'none';
    public const GEO_JSON_DEFAULT_NAME = 'geojson';
    
    private string $dataDirectory;
    private string $downloadDir;
    
    public function __construct(
        string $dataDirectory,
        private ?LoggerInterface $logger = null,
    ) {
        $this->dataDirectory = rtrim($dataDirectory, DIRECTORY_SEPARATOR);
        $this->downloadDir = $dataDirectory . DIRECTORY_SEPARATOR . self::DOWNLOAD_DIR;
    }
    
    /**
     * Main function that runs all updating process
     */
    public function updateData(): string
    {
        try {
            $this->logger?->info('Downloading data...');
            $this->downloadLastVersion();
            
            $this->logger?->info('Unzip data...');
            $this->unzipData($this->downloadDir . DIRECTORY_SEPARATOR . self::TIMEZONE_FILE_NAME . '.zip');
            
            $this->logger?->info('Rename timezones json...');
            return $this->renameTimezoneJson();
        } catch (GeoTimeZoneException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new UpdaterException('Error while update data', 100, $e);
        }
    }
    
    /**
     * Get complete json response from repo
     * @throws UpdaterException
     */
    private function getResponse(string $url): string
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $url);
            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            throw new UpdaterException('Error while get HTTP response', 100, $e);
        }
    }
    
    /**
     * Download zip file
     */
    private function getZipResponse(string $url, string $destinationPath = 'none'): void
    {
        try {
            $client = new Client();
            $client->request('GET', $url, [
                'sink' => $destinationPath,
            ]);
        } catch (GuzzleException $e) {
            throw new UpdaterException('Error while get HTTP response', 100, $e);
        }
    }
    
    /**
     * Get timezones json url
     */
    private function getGeoJsonUrl(string $data): string
    {
        $jsonResp = json_decode($data, true);
        
        foreach ($jsonResp['assets'] as $asset) {
            if (strpos($asset['name'], self::GEO_JSON_DEFAULT_NAME)) {
                return $asset['browser_download_url'];
            }
        }
        
        return self::GEO_JSON_DEFAULT_URL;
    }
    
    /**
     * Download last version reference repo
     */
    private function downloadLastVersion(): void
    {
        $response = $this->getResponse(self::REPO_HOST . self::REPO_PATH);
        $geoJsonUrl = $this->getGeoJsonUrl($response);
        if ($geoJsonUrl !== self::GEO_JSON_DEFAULT_URL) {
            if (!is_dir($this->dataDirectory)) {
                mkdir($this->dataDirectory);
            }
            if (!is_dir($this->downloadDir)) {
                mkdir($this->downloadDir);
            }
            $this->getZipResponse(
                $geoJsonUrl,
                $this->downloadDir . DIRECTORY_SEPARATOR . self::TIMEZONE_FILE_NAME . ".zip"
            );
        }
    }
    
    /**
     * Unzip data
     */
    private function unzipData(string $filePath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $zipName = basename($filePath, ".zip");
            if (!is_dir($this->downloadDir . DIRECTORY_SEPARATOR . $zipName)) {
                mkdir($this->downloadDir . DIRECTORY_SEPARATOR . $zipName);
            }
            
            $zip->extractTo($this->downloadDir . DIRECTORY_SEPARATOR . $zipName);
            $zip->close();
            unlink($filePath);
        }
    }
    
    /**
     * Rename downloaded timezones json file
     */
    private function renameTimezoneJson(): string
    {
        $path = realpath($this->downloadDir . DIRECTORY_SEPARATOR . self::TIMEZONE_FILE_NAME . '/');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $jsonPath = '';
        foreach ($files as $pathFile => $_) {
            if (strpos($pathFile, '.json')) {
                $jsonPath = $pathFile;
                break;
            }
        }
        $timezonesSourcePath = dirname($jsonPath) . DIRECTORY_SEPARATOR . self::TIMEZONE_FILE_NAME . '.json';
        rename($jsonPath, $timezonesSourcePath);
        
        return $timezonesSourcePath;
    }
}

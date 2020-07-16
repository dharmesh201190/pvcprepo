<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem\DriverPool;

/**
 * Abstract class for import source types
 *
 * @package Firebear\ImportExport\Model\Source\Type
 */
abstract class AbstractType extends DataObject
{
    /**
     * Temp directory for downloaded files
     */
    const IMPORT_DIR = 'var/import';

    /**
     * Temp directory for downloaded images
     */
    const MEDIA_IMPORT_DIR = 'pub/media/import';

    const EXPORT_DIR = 'var/export';

    const CSV_FILENAME_EXTENSION = '.csv';
    const JSON_FILENAME_EXTENSION = '.json';
    const XML_FILENAME_EXTENSION = '.xml';

    /**
     * Source type code
     *
     * @var string
     */
    protected $code;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\File\ReadFactory
     */
    protected $readFactory;

    /**
     * @var array
     */
    protected $metadata = [];

    protected $client;

    protected $exportModel;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteFactory
     */
    protected $writeFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Filesystem\File\WriteFactory
     */
    protected $fileWrite;

    /**
     * @var \Firebear\ImportExport\Model\Source\Factory
     */
    protected $factory;

    protected $formatFile;

    /**
     * AbstractType constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Firebear\ImportExport\Model\Filesystem\File\ReadFactory $readFactory
     * @param \Magento\Framework\Filesystem\Directory\WriteFactory $writeFactory
     * @param \Magento\Framework\Filesystem\File\WriteFactory $fileWrite
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Firebear\ImportExport\Model\Source\Factory $factory
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Firebear\ImportExport\Model\Filesystem\File\ReadFactory $readFactory,
        \Magento\Framework\Filesystem\Directory\WriteFactory $writeFactory,
        \Magento\Framework\Filesystem\File\WriteFactory $fileWrite,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Firebear\ImportExport\Model\Source\Factory $factory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->filesystem = $filesystem;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->readFactory = $readFactory;
        $this->writeFactory = $writeFactory;
        $this->timezone = $timezone;
        $this->fileWrite = $fileWrite;
        $this->factory = $factory;
    }

    /**
     * Prepare temp dir for import files
     *
     * @return string
     */
    protected function getImportPath()
    {
        return self::IMPORT_DIR . '/' . $this->code;
    }

    /**
     * Prepare temp dir for import images
     *
     * @return string
     */
    protected function getMediaImportPath()
    {
        return self::MEDIA_IMPORT_DIR . '/' . $this->code;
    }

    /**
     * Get file path
     *
     * @return bool|string
     */
    public function getImportFilePath()
    {
        if ($sourceType = $this->getImportSource()) {
            $filePath = $this->getData($sourceType . '_file_path');

            return $filePath;
        }

        return false;
    }

    /**
     * Get source type code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    abstract public function uploadSource();

    abstract public function importImage($importImage, $imageSting);

    abstract public function checkModified($timestamp);

    abstract protected function _getSourceClient();

    /**
     * @param $model
     */
    public function setExportModel($model)
    {
        $this->exportModel = $model;
    }

    /**
     * @return mixed
     */
    public function getExportModel()
    {
        return $this->exportModel;
    }

    /**
     * return file
     */
    protected function writeFile($path)
    {
        $newPath = $this->clearPath($path);
        $dir = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
        if (count($newPath) > 1) {
            $fileName = array_pop($newPath);
            $path = implode("/", $newPath);
            if (!$dir->isExist($path)) {
                $directory = $this->writeFactory->create($dir->getAbsolutePath($path));
                $directory->create();
            }
            $path = $dir->getAbsolutePath() . $path . "/";
        } else {
            $fileName = array_pop($newPath);
            $path = $dir->getAbsolutePath();
        }
        $file = $this->fileWrite->create(
            $path . $fileName,
            \Magento\Framework\Filesystem\DriverPool::FILE,
            "w"
        );
        $file->write($this->getExportModel()->export());
        $file->close();

        return true;
    }

    /**
     * @param $path
     *
     * @return array
     */
    protected function clearPath($path)
    {
        $arrayPath = explode("/", $path);
        $newArrayPath = [];
        foreach ($arrayPath as $partPath) {
            if (!empty($partPath)) {
                $newArrayPath[] = $partPath;
            }
        }

        return $newArrayPath;
    }

    /**
     * @return bool
     */
    public function check()
    {
        try {
            if ($client = $this->_getSourceClient()) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public function setUrl($importImage, $imageSting, $matches)
    {
        $url = str_replace($matches[0], '', $importImage);
        $read = $this->readFactory->create($url, DriverPool::HTTP);
        $this->directory->writeFile(
            $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting),
            $read->readAll()
        );
    }

    public function setFormatFile($file)
    {
        $this->formatFile = $file;

        return $this;
    }

    public function getFormatFile()
    {
        return $this->formatFile;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function convertUrlToFilename($url)
    {
        $parsedUrl = parse_url($url);
        $filename = str_replace('.', '_', $parsedUrl['host'])
            . str_replace('/', '_', $parsedUrl['path'])
            . constant('self::' . strtoupper($this->getData('type_file')) . '_FILENAME_EXTENSION');

        return $filename;
    }
}

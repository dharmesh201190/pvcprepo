<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DriverPool;

/**
 * Class Uploader
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @package Firebear\ImportExport\Model\Import
 */
class Uploader extends \Magento\CatalogImportExport\Model\Import\Uploader
{
    /**
     * Default User Agent chain to prevent 403 forbidden issue
     */
    const DEFAULT_HTTP_USER_AGENT = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)';

    private $httpScheme = 'http://';

    /**
     * Uploader constructor.
     *
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb
     * @param \Magento\MediaStorage\Helper\File\Storage $coreFileStorage
     * @param \Magento\Framework\Image\AdapterFactory $imageFactory
     * @param \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\File\ReadFactory $readFactory
     * @param \Firebear\ImportExport\Model\Filesystem\File\ReadFactory $fireReadFactory
     * @param null $filePath
     *
     * @throws LocalizedException
     */
    public function __construct(
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb,
        \Magento\MediaStorage\Helper\File\Storage $coreFileStorage,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\File\ReadFactory $readFactory,
        \Firebear\ImportExport\Model\Filesystem\File\ReadFactory $fireReadFactory,
        $filePath = null
    ) {
        parent::__construct(
            $coreFileStorageDb,
            $coreFileStorage,
            $imageFactory,
            $validator,
            $filesystem,
            $readFactory,
            $filePath
        );

        $this->_readFactory = $fireReadFactory;
    }

    /**
     * @param string $fileName
     * @param bool $renameFileOff
     * @param array $existingUpload
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws LocalizedException
     */
    public function move($fileName, $renameFileOff = false, $existingUpload = [])
    {
        $file_info = '';
        $mime_type = $this->getImageMimeType();
        if ($renameFileOff) {
            $this->setAllowRenameFiles(false);
        }
        $fileName = trim($fileName);
        if (preg_match('/\bhttps?:\/\//i', $fileName, $matches)) {
            if (\class_exists(\finfo::class)) {
                $file_info = new \finfo(FILEINFO_MIME_TYPE);
            }
            $url = str_replace($matches[0], '', $fileName);
            $urlProp = $this->parseUrl($this->httpScheme . $url);
            $hostname = $urlProp['host'];
            $path = $urlProp['path'];
            $path1 = \explode('/', $path);
            $name = str_replace('/', '_', end($path1));
            if (!$this->isImageQueryString($fileName)) {
                $ch = curl_init();
                $url = $this->httpScheme . $hostname . $path;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                if (isset($urlProp['user'], $urlProp['pass'])) {
                    curl_setopt($ch, CURLOPT_USERPWD, $urlProp['user'] . ':' . $urlProp['pass']);
                }
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, 1);
                $data = curl_exec($ch);
                $info = curl_getinfo($ch);

                curl_close($ch);
                if ($file_info instanceof \finfo) {
                    $mime_type = $file_info->buffer($data);
                }
            } else {
                $options = ['http' => ['user_agent' => self::DEFAULT_HTTP_USER_AGENT]];
                $context = stream_context_create($options);
                $data = $this->_directory->getDriver()->fileGetContents($fileName, null, $context);
                if ($file_info instanceof \finfo) {
                    $mime_type = $file_info->buffer($data);
                }
                if (!array_key_exists(\pathinfo($name, PATHINFO_EXTENSION), $this->_allowedMimeTypes)) {
                    $name .= $this->getImageMimeType($mime_type);
                }
            }
            $this->_directory->writeFile('var/cache/' . $name, $data);
            $read = $this->_readFactory->create(
                $this->_directory->getAbsolutePath() . 'var/cache/' . $name,
                DriverPool::FILE
            );
            $fileName = $name;
            if (!array_key_exists(\pathinfo($fileName, PATHINFO_EXTENSION), $this->_allowedMimeTypes)) {
                $fileName .= $this->getImageMimeType($mime_type);
            }
            $this->_directory->writeFile(
                $this->_directory->getRelativePath($this->getTmpDir() . '/' . $fileName),
                $read->readAll()
            );
        }

        $filePath = $this->_directory->getRelativePath($this->getTmpDir() . '/' . $fileName);

        $this->_setUploadFile($filePath);
        if ($key = \array_search($this->getUploadedFileName(), $existingUpload, false)) {
            $exp = \explode('.', $fileName);
            $newFileName = $exp[0] . '_' . $key . '.'. $exp[1];
        } else {
            $newFileName = $fileName;
        }
        $destDir = $this->_directory->getAbsolutePath($this->getDestDir());
        $result = $this->save($destDir, $newFileName);

        unset($result['path']);

        $result['name'] = self::getCorrectFileName($result['name']);
        return $result;
    }

    /**
     * @param string $mime_type
     *
     * @return string
     */
    private function getImageMimeType($mime_type = 'image/jpeg')
    {
        $fileExtension = \array_flip($this->_allowedMimeTypes)[$mime_type] ?? 'jpeg';
        return '.' . $fileExtension;
    }

    /**
     * @param $fileName
     *
     * @return bool
     */
    private function isImageQueryString($fileName)
    {
        return \strpos($fileName, '?') !== false;
    }

    /**
     * Create folder
     *
     * @param string $directory
     *
     * @return \Magento\Framework\File\Uploader
     * @throws LocalizedException
     */
    public function createDirectory($directory)
    {
        if (!$directory || !$this->_allowCreateFolders) {
            return $this;
        }

        $directory = (substr($directory, -1) == '/') ? substr($directory, 0, -1) : $directory;

        if (!(@is_dir($directory)
            || @mkdir($directory, 0777, true)
        )) {
            throw new LocalizedException(__(
                'Unable to create directory \'%1\'',
                $directory
            ));
        }
        return $this;
    }

    /**
     * @param $directory
     *
     * @return bool
     */
    public function isDirectoryWritable($directory)
    {
        return $this->_directory->isWritable($directory);
    }

    protected function parseUrl($path)
    {
        return parse_url($path);
    }
}

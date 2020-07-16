<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Filesystem\DriverPool;

/**
 * Class Rest
 *
 * @package Firebear\ImportExport\Model\Source\Type
 */
class Soap extends AbstractType
{
    const XML_FILENAME_EXTENSION = '.xml';

    /**
     * @var string
     */
    protected $code = 'soap';

    /**
     * @var string
     */
    protected $fileName;

    /**
     * Download remote source file to temporary directory
     *
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        if ($client = $this->_getSourceClient()) {
            if (!$this->fileName) {
                $fileName = $this->convertUrlToFilename($this->getData('request_url'));
                $filePath = $this->directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
                try {
                    $dirname = dirname($filePath);
                    if (!is_dir($dirname)) {
                        mkdir($dirname, 0775, true);
                    }
                } catch (\Exception $e) {
                    throw new  \Magento\Framework\Exception\LocalizedException(
                        __(
                            "Can't create local file /var/import/soap'. Please check files permissions. "
                            . $e->getMessage()
                        )
                    );
                }
                $options = $this->getSoapOptions();
                try {
                    $response = $client->__soapCall($this->getData('soap_call'), [$options]);
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __("Soap Call Error %s", $e->getMessage())
                    );
                }
                $fileMetadata = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $client->__getLastResponse());
                $fileMetadata = preg_replace('/\\sxmlns="\\S+"/', '', $fileMetadata);
                file_put_contents($filePath, (string)$fileMetadata);
                if ($fileMetadata) {
                    $this->fileName = $this->getImportPath() . '/' . $fileName;
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(__("No content from API call"));
                }
            }

            return $this->fileName;
        } else {
            throw new  \Magento\Framework\Exception\LocalizedException(__("Can't initialize %s client", $this->code));
        }
    }

    /**
     * Download remote images to temporary media directory
     *
     * @param $importImage
     * @param $imageSting
     *
     * @return bool
     */
    public function importImage($importImage, $imageSting)
    {
        $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
        $dirname = dirname($filePath);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0775, true);
        }
        if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $url = str_replace($matches[0], '', $importImage);
        } else {
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $sourceDir = dirname($sourceFilePath);
            $url = $sourceDir . '/' . $importImage;
            if (preg_match('/\bhttps?:\/\//i', $url, $matches)) {
                $url = str_replace($matches[0], '', $url);
            }
        }
        if ($url) {
            try {
                $driver = $this->getProperDriverCode($matches);
                $read = $this->readFactory->create($url, $driver);
                $this->directory->writeFile(
                    $filePath,
                    $read->readAll()
                );
            } catch (\Exception $e) {
            }
        }

        return true;
    }

    public function importImageCategory($importImage, $imageSting)
    {
        $filePath = $this->directory->getAbsolutePath('pub/media/catalog/category/' . $imageSting);
        $dirname = dirname($filePath);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0775, true);
        }
        if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $url = str_replace($matches[0], '', $importImage);
        } else {
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $sourceDir = dirname($sourceFilePath);
            $url = $sourceDir . '/' . $importImage;
            if (preg_match('/\bhttps?:\/\//i', $url, $matches)) {
                $url = str_replace($matches[0], '', $url);
            }
        }
        if ($url) {
            try {
                $driver = $this->getProperDriverCode($matches);
                $read = $this->readFactory->create($url, $driver);
                $this->directory->writeFile(
                    $filePath,
                    $read->readAll()
                );
            } catch (\Exception $e) {
            }
        }

        return true;
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param int $timestamp
     *
     * @return bool|int
     */
    public function checkModified($timestamp)
    {
        return true;
    }

    /**
     * Prepare and return Driver client
     *
     * @return \SoapClient
     */
    protected function _getSourceClient()
    {
        if (!$this->client) {
            $wsdl = $this->getData('request_url');
            $soapOptions = $this->getSoapOptions();
            $this->client = new \SoapClient($wsdl . '?wsdl', $soapOptions);
        }
        return $this->client;
    }

    /**
     * @return array
     */
    private function getSoapOptions()
    {
        $soapOptions = $this->getOptionsData($this->getData('options'));
        $defaultSoapOptions = [
            'trace'          => true,
            'soap_version'   => (int)$this->getData('soap_version'),
            'stream_context' => stream_context_create(
                [
                    'ssl' => [
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ],
                ]
            ),
        ];
        $soapOptions = \array_merge($soapOptions, $defaultSoapOptions);
        return $soapOptions;
    }

    /**
     * @param string      $data
     *
     * @return array data
     */
    public function getOptionsData($data)
    {
        $data = trim(preg_replace('/\s+/', '', $data));
        $data = json_decode($data, null);
        return (array)$data;
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
            . constant("self::" . strtoupper($this->getData('type_file')) . "_FILENAME_EXTENSION");

        return $filename;
    }
}

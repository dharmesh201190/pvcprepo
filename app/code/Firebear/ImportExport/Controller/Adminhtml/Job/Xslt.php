<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\JobFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;

class Xslt extends JobController
{
    /**
     * @var \Magento\Framework\FilesystemFactory
     */
    protected $fileSystem;

    /**
     * @var \Firebear\ImportExport\Model\ImportFactory
     */
    protected $importFactory;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     * @var \Firebear\ImportExport\Model\Output\Xslt
     */
    protected $modelOutput;

    /**
     * @param Context $context
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param \Magento\Framework\FilesystemFactory $filesystemFactory
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param \Firebear\ImportExport\Model\ImportFactory $importFactory
     * @param \Firebear\ImportExport\Model\Output\Xslt $modelOutput
     */
    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        \Magento\Framework\FilesystemFactory $filesystemFactory,
        \Magento\Framework\Filesystem\Io\File $file,
        \Firebear\ImportExport\Model\ImportFactory $importFactory,
        \Firebear\ImportExport\Model\Output\Xslt $modelOutput
    ) {
        parent::__construct($context, $jobFactory, $repository);
        $this->fileSystem = $filesystemFactory;
        $this->importFactory = $importFactory;
        $this->file = $file;
        $this->modelOutput = $modelOutput;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $messages = [];
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $formData = $this->getRequest()->getParam('form_data');
            $importData = [];

            foreach ($formData as $data) {
                $index = strstr($data, '+', true);
                $importData[$index] = substr($data, strpos($data, '+') + 1);
            }
            $directory = $this->fileSystem->create()->getDirectoryWrite(DirectoryList::ROOT);
            if ($importData['import_source'] != 'file') {
                if (!in_array($importData['import_source'], ['rest', 'soap'])) {
                    $importData[$importData['import_source'] . '_file_path'] = $importData['file_path'];
                }
                $importModel = $this->importFactory->create();
                $importModel->setData($importData);
                $source = $importModel->getSource();
                $source->setFormatFile($importData['type_file']);
                $file = $directory->getAbsolutePath() . "/" . $source->uploadSource();
            } else {
                $file = $directory->getAbsolutePath() . "/" . $importData['file_path'];
            }
            if (strpos($file, $directory->getAbsolutePath()) === false) {
                $file = $directory->getAbsolutePath() . "/" . $file;
            }
            $dest = $this->file->read($file);
            $messages = [];
            try {
                $result = $this->modelOutput->convert($dest, $importData['xslt']);
                return $resultJson->setData(
                    [
                        'result' => $result
                    ]
                );
            } catch (\Exception $e) {
                $messages[] = $e->getMessage();
            }

            return $resultJson->setData(
                [
                    'error' => $messages
                ]
            );
        }
    }
}

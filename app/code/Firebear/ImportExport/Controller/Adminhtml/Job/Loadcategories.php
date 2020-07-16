<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Backend\App\Action\Context;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Model\Job\Processor;
use Firebear\ImportExport\Helper\Assistant;
use Magento\Framework\Serialize\SerializerInterface;

class Loadcategories extends JobController
{
    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Assistant
     */
    protected $ieAssistant;

    /**
     * @param Context $context
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param Processor $processor
     * @param SerializerInterface $serializer
     * @param Assistant $ieAssistant
     */
    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        Processor $processor,
        SerializerInterface $serializer,
        Assistant $ieAssistant
    ) {
        parent::__construct($context, $jobFactory, $repository);
        $this->processor = $processor;
        $this->serializer = $serializer;
        $this->ieAssistant = $ieAssistant;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $categories = [];
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $type = $this->getRequest()->getParam('type');
            $locale = $this->getRequest()->getParam('language');
            $formData = $this->getRequest()->getParam('form_data');
            $sourceType = $this->getRequest()->getParam('source_type');
            $importData = [];
            foreach ($formData as $data) {
                if (is_array($data)) {
                    $importData['mappingData'][] = $data;
                } elseif (strpos($data, 'records+') !== false) {
                    $exData = explode('+', $data);
                    $exData = $this->getContents($exData[1], '[', ']');
                    if (!empty($exData[0])) {
                        $importData['mappingData'] = $this->serializer->unserialize('[' . $exData[0] . ']');
                    }
                } else {
                    $index = strstr($data, '+', true);
                    $index = str_replace($sourceType . '[', '', $index);
                    $index = str_replace(']', '', $index);
                    $importData[$index] = substr($data, strpos($data, '+') + 1);
                }
            }
            $importData['platforms'] = $type;
            $importData['locale'] = $locale;
            $importData['import_source'] = $importData['import_source'] ?? '';
            $importData['file_path'] = $importData['file_path'] ?? '';
            if (!empty($importData['type_file'])) {
                $this->processor->setTypeSource($importData['type_file']);
            }
            if (!in_array($importData['import_source'], ['rest', 'soap'])) {
                $importData[$sourceType . '_file_path'] = $importData['file_path'];
            }

            try {
                //load categories map
                $importModel = $this->processor->getImportModel()->setData($importData);
                if ($importModel->getEntity() == 'catalog_product') {
                    $categories = $importModel->getCategories($importData);
                    $categories = $this->ieAssistant->parsingCategories(
                        $categories,
                        $importData['categories_separator']
                    );
                    $categories = array_unique($categories);
                }
            } catch (\Exception $e) {
                return $resultJson->setData(['error' => $e->getMessage()]);
            }

            return $resultJson->setData(
                [
                    'categories' => $categories
                ]
            );
        }
    }

    /**
     * @param string $str
     * @param string $startDelimiter
     * @param string $endDelimiter
     * @return array
     */
    public function getContents($str, $startDelimiter, $endDelimiter)
    {
        $contents = [];
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $contentStart = $contentEnd = 0;
        while (false !== ($contentStart = strpos($str, $startDelimiter, $startFrom))) {
            $contentStart += $startDelimiterLength;
            $contentEnd = strpos($str, $endDelimiter, $contentStart);
            if (false === $contentEnd) {
                break;
            }
            $contents[] = substr($str, $contentStart, $contentEnd - $contentStart);
            $startFrom = $contentEnd + $endDelimiterLength;
        }

        return $contents;
    }
}

<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Controller\Adminhtml\Export\Job as JobController;
use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Model\ExportJobFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Serialize\SerializerInterface;

class Run extends JobController
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $url;

    /**
     * @param Context $context
     * @param ExportJobFactory $exportJobFactory
     * @param ExportJobRepositoryInterface $exportRepository
     * @param SerializerInterface $serializer
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        ExportJobFactory $exportJobFactory,
        ExportJobRepositoryInterface $exportRepository,
        SerializerInterface $serializer,
        Data $helper
    ) {
        parent::__construct($context, $exportJobFactory, $exportRepository);
        $this->serializer = $serializer;
        $this->helper = $helper;
        $this->url = $context->getBackendUrl();
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $result[0] = true;
        $exportFile = '';
        $lastEntityId = '';
        if ($this->getRequest()->isAjax()
            && $this->getRequest()->getParam('file')
            && $this->getRequest()->getParam('id')
        ) {
            try {
                session_write_close();
                ignore_user_abort(true);
                set_time_limit(0);
                ob_implicit_flush();
                $id = $this->getRequest()->getParam('id');
                $file = $this->getRequest()->getParam('file');
                $lastEntityId = $this->getRequest()->getParam('last_entity_value');

                if ($lastEntityId) {
                    $this->updateLastEntityId($id, $lastEntityId);
                }
                $exportFile = $this->helper->runExport($id, $file);
                $result = $this->helper->getResultProcessor();

                if (isset($result[1])
                    && $result[1] > $lastEntityId
                ) {
                    $lastEntityId = $result[1];
                }
            } catch (\Exception $e) {
                $result[0] = false;
            }

            return $resultJson->setData([
                'result' => $result[0],
                'file' => $this->url->getUrl(
                    'import/export_job/download',
                    ['file' => str_replace("/", "|", $exportFile)]
                ),
                'last_entity_id' => $lastEntityId,
            ]);
        }
    }

    /**
     * @param $jobId
     * @param $lastEntityId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateLastEntityId($jobId, $lastEntityId)
    {
        $exportJob = $this->exportRepository->getById($jobId);
        $sourceData = $this->serializer->unserialize($exportJob->getExportSource());
        $sourceData = array_merge(
            $sourceData,
            [
                'last_entity_id' => $lastEntityId,
            ]
        );
        $sourceData = $this->serializer->serialize($sourceData);
        $exportJob->setExportSource($sourceData);
        $this->exportRepository->save($exportJob);
    }
}

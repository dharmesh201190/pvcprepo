<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Export\Job as JobController;
use Firebear\ImportExport\Helper\Data;
use Magento\Backend\App\Action\Context;
use Firebear\ImportExport\Model\ExportJobFactory;
use Firebear\ImportExport\Api\ExportJobRepositoryInterface;

class Status extends JobController
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Context $context
     * @param ExportJobFactory $jobFactory
     * @param ExportJobRepositoryInterface $repository
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        ExportJobFactory $jobFactory,
        ExportJobRepositoryInterface $repository,
        Data $helper
    ) {
        parent::__construct($context, $jobFactory, $repository);
        $this->helper = $helper;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $file = $this->getRequest()->getParam('file');
            $counter = $this->getRequest()->getParam('number', 0);
            $console = $this->helper->scopeRun($file, $counter);

            return $resultJson->setData(
                [
                    'console' => $console
                ]
            );
        }
    }
}

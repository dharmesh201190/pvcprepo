<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Helper\Data;
use Magento\Backend\App\Action\Context;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;

class Check extends JobController
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Context $context
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
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
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        if ($this->getRequest()->isAjax()) {
            $file = $this->getRequest()->getParam('file');
            $job = $this->getRequest()->getParam('job');
            $result = $this->helper->countData($file, $job);
            return $resultJson->setData(
                [
                    'result' => $result
                ]
            );
        }
    }
}

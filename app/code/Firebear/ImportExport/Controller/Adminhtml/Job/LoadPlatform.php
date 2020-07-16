<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\Import\Platforms;
use Firebear\ImportExport\Model\JobFactory;
use Magento\Backend\App\Action\Context;

class LoadPlatform extends JobController
{
    /**
     * @var Platforms
     */
    protected $_platforms;

    /**
     * @param Context $context
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param Platforms $platforms
     */
    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        Platforms $platforms
    ) {
        $this->_platforms = $platforms;

        parent::__construct($context, $jobFactory, $repository);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        if ($this->getRequest()->isAjax()) {
            $entityType = $this->getRequest()->getParam('entity');
            $platformList = $this->_platforms->getPlatformList($entityType);
            return $resultJson->setData($platformList);
        }
    }
}

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

class Downcategories extends JobController
{
    /**
     * @var \Firebear\ImportExport\Ui\Component\Form\Categories\Options
     */
    protected $categories;

    /**
     * @param Context $context
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param \Firebear\ImportExport\Ui\Component\Form\Categories\Options $categories
     */
    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        \Firebear\ImportExport\Ui\Component\Form\Categories\Options $categories
    ) {
        parent::__construct($context, $jobFactory, $repository);
        $this->categories = $categories;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        if ($this->getRequest()->isAjax()) {
            $options = $this->categories->toOptionArray();

            return $resultJson->setData($options);
        }
        return $resultJson->setData([]);
    }
}

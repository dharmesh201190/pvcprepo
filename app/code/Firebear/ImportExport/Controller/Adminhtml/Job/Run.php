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

class Run extends JobController
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
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $result = false;
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
                $this->helper->getProcessor()->inConsole = 0;
                $result = $this->helper->runImport($id, $file);
            } catch (\Exception $e) {
                $result = false;
            }

            return $resultJson->setData(['result' => $result]);
        }
    }
}

<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Model\ExportJobFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

class Edit extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param Context $context
     * @param ExportJobFactory $exportJobFactory
     * @param ExportJobRepositoryInterface $exportRepository
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        ExportJobFactory $exportJobFactory,
        ExportJobRepositoryInterface $exportRepository,
        Registry $coreRegistry
    ) {
        parent::__construct($context, $exportJobFactory, $exportRepository);
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @return $this|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $jobId = $this->getRequest()->getParam('entity_id');
        $model = $this->exportJobFactory->create();
        if ($jobId) {
            $model = $this->exportRepository->getById($jobId);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This job is no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('export_job', $model);

        $resultPage = $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Firebear_ImportExport::export_job');
        $resultPage->getConfig()->getTitle()->prepend(__('Export Jobs'));
        $resultPage->addBreadcrumb(__('Export'), __('Export'));
        $resultPage->addBreadcrumb(
            $jobId ? __('Edit Job') : __('New Job'),
            $jobId ? __('Edit Job') : __('New Job')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Jobs'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getTitle() : __('New Job'));

        return $resultPage;
    }
}

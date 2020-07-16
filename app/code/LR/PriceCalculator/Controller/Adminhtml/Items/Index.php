<?php
namespace LR\PriceCalculator\Controller\Adminhtml\Items;

class Index extends \LR\PriceCalculator\Controller\Adminhtml\Items
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('LR_PriceCalculator::material');
        $resultPage->getConfig()->getTitle()->prepend(__('Materials'));
        $resultPage->addBreadcrumb(__('Materials'), __('Materials'));
        $resultPage->addBreadcrumb(__('Materials'), __('Materials'));
        return $resultPage;
    }
}
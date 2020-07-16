<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Firebear\ImportExport\Model\ResourceModel\ExportJob\CollectionFactory;

class AbstractMass extends \Magento\Backend\App\Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Firebear\ImportExport\Api\ExportJobRepositoryInterface
     */
    protected $repository;

    /**
     * MassDelete constructor.
     *
     * @param Context                                                 $context
     * @param Filter                                                  $filter
     * @param CollectionFactory                                       $collectionFactory
     * @param \Firebear\ImportExport\Api\ExportJobRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Firebear\ImportExport\Api\ExportJobRepositoryInterface $repository
    ) {
        parent::__construct($context);
        $this->filter            = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->repository        = $repository;
    }

    /**
     * @return mixed
     */
    protected function getCollection()
    {
        return $this->filter->getCollection($this->collectionFactory->create());
    }

    /**
     * @param $message
     * @param $size
     *
     * @return mixed
     */
    protected function getRedirect($message, $size)
    {
        $this->messageManager->addSuccessMessage(__($message, $size));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }

    public function execute()
    {
        return true;
    }
}

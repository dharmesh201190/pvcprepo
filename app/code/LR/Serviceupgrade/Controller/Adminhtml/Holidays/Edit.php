<?php
namespace LR\Serviceupgrade\Controller\Adminhtml\Holidays;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Edit extends \Magento\Backend\App\Action
{
     /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\Page
     */
    protected $resultPage;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
	public function execute()
    {
		
		
		// 1. Get ID and create model
        $id = $this->getRequest()->getParam('id');
		
        $model = $this->_objectManager->create('LR\Serviceupgrade\Model\Holiday');
		
		$registryObject = $this->_objectManager->get('Magento\Framework\Registry');
		
        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This Holiday no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }
        // 3. Set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
		$registryObject->register('holiday', $model);
        
        $this->resultPage = $this->resultPageFactory->create();  
        $this->resultPage->setActiveMenu('LR_Serviceupgrade::holiday');
        $this->resultPage->getConfig()->getTitle()->prepend(__('Production Holidays'));
        return $this->resultPage;

		/*$this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();*/
    }
}

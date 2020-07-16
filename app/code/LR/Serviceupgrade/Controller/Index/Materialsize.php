<?php
namespace LR\Serviceupgrade\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;

class Materialsize extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    public function __construct(
    Context $context, PageFactory $resultPageFactory,
    \LR\Serviceupgrade\Helper\Data $lr_helper
    )
    {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $calculatedWidth = $this->getRequest()->getParam("calculatedWidth");
        $calculatedHeight = $this->getRequest()->getParam("calculatedHeight");
        $selectedOption = $this->getRequest()->getParam("selectedOption");

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultarray['status'] = 'fail';

        if($calculatedWidth != '' && $calculatedHeight != '' &&  $selectedOption != '-- Please Select --'){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $Materialtype = $objectManager->create('LR\Serviceupgrade\Model\ResourceModel\Materialtype\Collection');
            $Materialtype->addFieldToFilter('material_type', $selectedOption);
            $checkFlagVar = false;
            $validationMessage = '';
            if (count($Materialtype) > 0) {
                        foreach($Materialtype as $material){
                            if($calculatedWidth > $material['max_width']){
                                $checkFlagVar = true;
                                $validationMessage = $material['custom_message'];
                            }
                            if($calculatedHeight > $material['max_height']){
                                $checkFlagVar = true;
                                $validationMessage = $material['custom_message'];
                            }
                        }
            }

            if($checkFlagVar){
                $resultarray['status'] = 'success';
                $resultarray['html'] = strip_tags($validationMessage);
            }
        }
        $resultJson->setData($resultarray);
        return $resultJson;







    }

}

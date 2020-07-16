<?php

namespace LR\PriceCalculator\Controller\CalculatePrice;

use Magento\Framework\App\Action\Context;

class GetFinish extends \Magento\Framework\App\Action\Action
{
    protected $jsonResultFactory;
    protected $_pricecalculator;

    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \LR\PriceCalculator\Model\PriceCalculatorFactory $pricecalculator
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->_pricecalculator = $pricecalculator;
        parent::__construct($context);
    }
    public function execute()
    {
        $material = $this->getRequest()->getParam('material');
        
        $getFinish = $this->_pricecalculator->create()->getCollection()->addFieldToFilter('status', 1)->addFieldToFilter('material', $material)->load();
        
        $response = array();
        foreach($getFinish as $finish)
        {
            $response['test'][] = "<option value='".$finish->getFinish()."'>".$finish->getFinish()."</option>";     
        }

        return $this->jsonResultFactory->create()->setData($response);
    }
}

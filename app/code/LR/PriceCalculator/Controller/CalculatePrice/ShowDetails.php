<?php
namespace LR\PriceCalculator\Controller\CalculatePrice;

use Magento\Framework\App\Action\Action;

class ShowDetails extends Action
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }

}
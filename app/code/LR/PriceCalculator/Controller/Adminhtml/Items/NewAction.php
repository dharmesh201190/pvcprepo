<?php
namespace LR\PriceCalculator\Controller\Adminhtml\Items;

class NewAction extends \LR\PriceCalculator\Controller\Adminhtml\Items
{

    public function execute()
    {
        $this->_forward('edit');
    }
}

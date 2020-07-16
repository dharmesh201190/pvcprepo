<?php
namespace LR\PriceCalculator\Model;

use Magento\Framework\Model\AbstractModel;

class PriceCalculator extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('LR\PriceCalculator\Model\ResourceModel\PriceCalculator');
    }
}
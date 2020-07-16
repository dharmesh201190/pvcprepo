<?php
namespace LR\PriceCalculator\Model\ResourceModel\PriceCalculator;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'pricecalculator_id';
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'LR\PriceCalculator\Model\PriceCalculator',
            'LR\PriceCalculator\Model\ResourceModel\PriceCalculator'
        );
    }
}
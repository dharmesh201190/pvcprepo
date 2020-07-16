<?php
namespace LR\PriceCalculator\Model\ResourceModel;

class PriceCalculator extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('lr_pricecalculator', 'pricecalculator_id');   //here "lr_pricecalculator" is table name and "pricecalculator_id" is the primary key of custom table
    }
}
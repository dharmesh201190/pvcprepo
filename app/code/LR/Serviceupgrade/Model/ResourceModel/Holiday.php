<?php
namespace LR\Serviceupgrade\Model\ResourceModel;


/**
 * Class Serviceupgrade
 * @package LR\Serviceupgrade\Model\ResourceModel
 */
class Holiday extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     *get Customer review data by id
     */
    protected function _construct()
    {
        $this->_init('lr_holidays', "id");
    }
}
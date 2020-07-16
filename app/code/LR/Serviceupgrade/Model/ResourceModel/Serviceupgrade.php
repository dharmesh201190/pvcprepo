<?php
namespace LR\Serviceupgrade\Model\ResourceModel;


/**
 * Class Serviceupgrade
 * @package LR\Serviceupgrade\Model\ResourceModel
 */
class Serviceupgrade extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     *get Customer review data by id
     */
    protected function _construct()
    {
        $this->_init('lr_serviceupgrade', "id");
    }
}
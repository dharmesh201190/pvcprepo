<?php

namespace LR\Serviceupgrade\Model\ResourceModel\Holiday;

/**
 * Class Collection
 * @package LR\Serviceupgrade\Model\ResourceModel\Serviceupgrade
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('LR\Serviceupgrade\Model\Holiday', 'LR\Serviceupgrade\Model\ResourceModel\Holiday');
    }

}
?>
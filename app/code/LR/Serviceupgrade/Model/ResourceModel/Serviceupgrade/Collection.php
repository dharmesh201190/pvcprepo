<?php

namespace LR\Serviceupgrade\Model\ResourceModel\Serviceupgrade;

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
        $this->_init('LR\Serviceupgrade\Model\Serviceupgrade', 'LR\Serviceupgrade\Model\ResourceModel\Serviceupgrade');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

}
?>
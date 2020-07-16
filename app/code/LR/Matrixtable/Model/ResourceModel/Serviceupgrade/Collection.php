<?php

namespace LR\Matrixtable\Model\ResourceModel\Serviceupgrade;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{ 
    protected function _construct()
    {
        $this->_init('LR\Matrixtable\Model\Serviceupgrade', 'LR\Matrixtable\Model\ResourceModel\Serviceupgrade');        
    }
}
?>
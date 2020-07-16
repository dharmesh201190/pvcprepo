<?php
namespace LR\Serviceupgrade\Model;

/**
 * Class Serviceupgrade
 * @package LR\Serviceupgrade\Model
 */
class Materialtype extends \Magento\Framework\Model\AbstractModel {

    /**
     *Serviceupgrade resource model
     */
    protected function _construct() {
        $this->_init('LR\Serviceupgrade\Model\ResourceModel\Materialtype');
    }
}
<?php
namespace LR\Serviceupgrade\Model;

/**
 * Class Serviceupgrade
 * @package LR\Serviceupgrade\Model
 */
class Serviceupgrade extends \Magento\Framework\Model\AbstractModel {

    /**
     * Serviceupgrade constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     *Serviceupgrade resource model
     */
    protected function _construct() {
        $this->_init('LR\Serviceupgrade\Model\ResourceModel\Serviceupgrade');
    }
}